<?php

namespace App\Listeners;

use App\Events\DocumentActivityLoggedEvent;
use App\Models\DocumentTransactionLog;
use App\Models\OfficeLibrary;

class StoreDocumentActivityLog
{
    public function handle(DocumentActivityLoggedEvent $event): void
    {
        // Resolve office from user or event payload
        $office = $event->user->office ?? null;

        $officeId   = $office->id ?? $event->office['id'] ?? null;
        $officeName = $office->office_name ?? $event->office['office_name'] ?? null;
        $officeType = $event->office['office_type'] ?? null;

        if (!$officeId && $officeName) {
            $officeModel = OfficeLibrary::where('office_name', $officeName)->first();
            if ($officeModel) {
                $officeId   = $officeModel->id;
                $officeName = $officeModel->office_name;
            }
        }

        if (empty($officeId)) {
            \Log::warning('StoreDocumentActivityLog: unable to resolve office_id for event', ['event' => $event]);
            return;
        }

        // Routed office (optional, only if not multiple)
        $routedOfficeId   = null;
        $routedOfficeName = null;
        $routedOfficeType = null;

        if ($event->routing_type !== 'multiple') {
            $routedOfficeId   = $event->routed_office['id'] ?? null;
            $routedOfficeName = $event->routed_office['office_name'] ?? null;
            $routedOfficeType = $event->routed_office['office_type'] ?? null;
        }

        // Build activity message
        $activity = match ($event->status) {
            'Profiled' => "Document profiled and assigned to {$officeName} ({$officeType})",
            'Received' => "Document received by {$officeName} ({$officeType}) for processing",
            'Released' => $event->routing_type === 'multiple'
                ? "Document released to multiple offices"
                : ($routedOfficeName
                    ? "Document released to {$routedOfficeName} ({$routedOfficeType})"
                    : "Document released"),
            'Forwarded' => $event->routing_type === 'multiple'
                ? "Document forwarded to multiple offices"
                : ($routedOfficeName
                    ? "Document forwarded to {$routedOfficeName} ({$routedOfficeType})"
                    : "Document forwarded"),
            'Archived' => "Document archived by {$officeName} ({$officeType})",
            'Returned To Sender' => "Document returned to sender from {$officeName} ({$officeType})",
            default => $event?->activity ?: "Activity recorded",
        };

        DocumentTransactionLog::create([
            'document_no'             => $event->documentNo,
            'transaction_no'          => $event->transactionNo,
            'office_id'               => $officeId,
            'office_name'             => $officeName,
            'routed_office_id'        => $routedOfficeId,
            'routed_office_name'      => $routedOfficeName,
            'status'                  => $event->status,
            'action_taken'            => $event->actionTaken,
            'activity'                => $activity,
            'remarks'                 => $event->remarks,
            'assigned_personnel_id'   => $event->user->id,
            'assigned_personnel_name' => $event->user->fullName(),
        ]);
    }
}
