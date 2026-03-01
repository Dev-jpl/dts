<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentTransaction;

/**
 * DocumentStatusService
 *
 * Single source of truth for computing and persisting Document.status.
 * Call evaluate() after every transaction status change.
 *
 * RULES:
 *   Closed is terminal — never overridden by this service.
 *   ANY transaction Processing → Active
 *   ALL transactions Completed  → Completed
 *   ANY transaction Returned (and none Processing) → Returned
 *
 * Priority: Closed > Active (any Processing) > Completed (all) > Returned (any)
 *
 * Never set document.status manually anywhere — always call this service.
 */
class DocumentStatusService
{
    /**
     * Evaluate and persist the correct status for a document.
     *
     * @return 'Draft'|'Active'|'Returned'|'Completed'|'Closed'|''
     */
    public static function evaluate(string $documentNo): string
    {
        $document = Document::where('document_no', $documentNo)
            ->lockForUpdate()
            ->first();

        if (!$document) {
            return '';
        }

        // Closed is terminal — this service never overrides it.
        if ($document->status === 'Closed') {
            return 'Closed';
        }

        $transactions = DocumentTransaction::where('document_no', $documentNo)->get();

        if ($transactions->isEmpty()) {
            return $document->status;
        }

        $anyProcessing = $transactions->contains(fn($t) => $t->status === 'Processing');
        $allCompleted  = $transactions->every(fn($t) => $t->status === 'Completed');
        $anyReturned   = $transactions->contains(fn($t) => $t->status === 'Returned');

        $newStatus = match (true) {
            $anyProcessing => 'Active',
            $allCompleted  => 'Completed',
            $anyReturned   => 'Returned',
            default        => 'Active',
        };

        if ($document->status !== $newStatus) {
            $document->update(['status' => $newStatus]);
        }

        return $newStatus;
    }
}
