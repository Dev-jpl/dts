<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentTransaction;
use App\Models\DocumentTransactionLog;
use Illuminate\Support\Collection;

/**
 * NotificationService
 *
 * Centralised dispatch point for all DTS notifications.
 * Each method corresponds to a trigger defined in CLAUDE.md.
 *
 * Current state: stubs — log to Laravel's default log channel.
 * Wire up to actual channels (database, mail, push) in a future phase.
 *
 * TRIGGER MAP (from CLAUDE.md):
 *   Release                → All recipients including CC/BCC
 *   Subsequent Release     → Target office only
 *   Receive (default)      → Origin office
 *   Receive (CC/BCC)       → Nobody
 *   Sequential step active → Next recipient in sequence
 *   Mark as Done           → Origin office
 *   Forward                → New recipient(s)
 *   Return to Sender       → Origin + all halted pending (with reason+remarks)
 *   Routing Halted         → Each halted office (with reason+remarks)
 *   Reply                  → Origin office
 *   Official Note added    → All active participants
 *   Overdue threshold      → Recipient + Origin office
 *   Close (forced)         → Pending recipients if routing halted
 */
class NotificationService
{
    // ─────────────────────────────────────────────────────────────────────────
    // Release
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Release → notify all recipients (default, CC, BCC).
     */
    public static function onRelease(DocumentTransaction $transaction): void
    {
        $recipients = $transaction->recipients->where('isActive', true);

        foreach ($recipients as $recipient) {
            self::dispatch($recipient->office_id, 'initial_release', [
                'transaction_no' => $transaction->transaction_no,
                'document_no'    => $transaction->document_no,
                'subject'        => $transaction->subject,
                'action_type'    => $transaction->action_type,
                'from_office'    => $transaction->office_name,
                'recipient_type' => $recipient->recipient_type,
            ]);
        }
    }

    /**
     * Subsequent Release → notify the target office only.
     */
    public static function onSubsequentRelease(DocumentTransaction $transaction, string $targetOfficeId, string $targetOfficeName): void
    {
        self::dispatch($targetOfficeId, 'subsequent_release', [
            'transaction_no'     => $transaction->transaction_no,
            'document_no'        => $transaction->document_no,
            'subject'            => $transaction->subject,
            'from_office'        => $transaction->office_name,
            'target_office_id'   => $targetOfficeId,
            'target_office_name' => $targetOfficeName,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Receive
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Receive (default recipient) → notify the origin office.
     * CC/BCC receives do not trigger notifications.
     */
    public static function onReceive(DocumentTransactionLog $log): void
    {
        $log->loadMissing('transaction.recipients');
        $transaction = $log->transaction;

        $recipient = $transaction->recipients
            ->where('office_id', $log->office_id)
            ->first();

        if (!$recipient || $recipient->recipient_type !== 'default') {
            return; // CC/BCC: no notification
        }

        self::dispatch($transaction->office_id, 'document_received', [
            'transaction_no'        => $transaction->transaction_no,
            'document_no'           => $transaction->document_no,
            'subject'               => $transaction->subject,
            'receiving_office_id'   => $log->office_id,
            'receiving_office_name' => $log->office_name,
        ]);
    }

    /**
     * Sequential routing: the next step just became active → notify the next recipient.
     */
    public static function onSequentialStepActivated(DocumentTransaction $transaction, string $nextOfficeId, string $nextOfficeName): void
    {
        self::dispatch($nextOfficeId, 'sequential_step_activated', [
            'transaction_no'   => $transaction->transaction_no,
            'document_no'      => $transaction->document_no,
            'subject'          => $transaction->subject,
            'action_type'      => $transaction->action_type,
            'from_office'      => $transaction->office_name,
            'next_office_id'   => $nextOfficeId,
            'next_office_name' => $nextOfficeName,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Terminal Actions
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mark as Done → notify the origin office.
     */
    public static function onMarkAsDone(DocumentTransaction $transaction, string $actingOfficeId, string $actingOfficeName): void
    {
        self::dispatch($transaction->office_id, 'marked_as_done', [
            'transaction_no'     => $transaction->transaction_no,
            'document_no'        => $transaction->document_no,
            'subject'            => $transaction->subject,
            'acting_office_id'   => $actingOfficeId,
            'acting_office_name' => $actingOfficeName,
        ]);
    }

    /**
     * Forward → notify the new recipient(s).
     *
     * @param  array<array{office_id: string, office_name: string}>  $newRecipients
     */
    public static function onForward(DocumentTransactionLog $log, array $newRecipients): void
    {
        $log->loadMissing('transaction');
        $transaction = $log->transaction;

        foreach ($newRecipients as $recipient) {
            self::dispatch($recipient['office_id'], 'document_forwarded', [
                'transaction_no'     => $transaction->transaction_no,
                'document_no'        => $transaction->document_no,
                'subject'            => $transaction->subject,
                'action_type'        => $transaction->action_type,
                'from_office'        => $log->office_name,
                'target_office_id'   => $recipient['office_id'],
                'target_office_name' => $recipient['office_name'],
            ]);
        }
    }

    /**
     * Return to Sender → notify the origin office + all halted offices.
     *
     * @param  array<array{office_id: string, office_name: string}>  $haltedOffices
     */
    public static function onReturnToSender(DocumentTransactionLog $log, array $haltedOffices): void
    {
        $log->loadMissing('transaction');
        $transaction = $log->transaction;

        $payload = [
            'transaction_no'        => $transaction->transaction_no,
            'document_no'           => $transaction->document_no,
            'subject'               => $transaction->subject,
            'returning_office_id'   => $log->office_id,
            'returning_office_name' => $log->office_name,
            'reason'                => $log->reason,
            'remarks'               => $log->remarks,
        ];

        // Notify origin
        self::dispatch($transaction->office_id, 'returned_to_sender', $payload);

        // Notify each halted office
        foreach ($haltedOffices as $office) {
            if ($office['office_id'] === $transaction->office_id) {
                continue; // already notified above
            }
            self::dispatch($office['office_id'], 'routing_halted', $payload);
        }
    }

    /**
     * Reply → notify the origin office of the original transaction.
     */
    public static function onReply(DocumentTransaction $originalTransaction, string $replyingOfficeId, string $replyingOfficeName): void
    {
        self::dispatch($originalTransaction->office_id, 'document_replied', [
            'transaction_no'       => $originalTransaction->transaction_no,
            'document_no'          => $originalTransaction->document_no,
            'subject'              => $originalTransaction->subject,
            'replying_office_id'   => $replyingOfficeId,
            'replying_office_name' => $replyingOfficeName,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Notes / Overdue / Close
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Official Note added → notify all active participants.
     * Active participants = origin + isActive=true default recipients across all transactions.
     */
    public static function onOfficialNoteAdded(string $documentNo): void
    {
        $document = Document::with(['transactions.recipients'])
            ->where('document_no', $documentNo)
            ->first();

        if (!$document) {
            return;
        }

        $officeIds = collect([$document->office_id]);

        foreach ($document->transactions as $transaction) {
            $transaction->recipients
                ->where('recipient_type', 'default')
                ->where('isActive', true)
                ->each(fn($r) => $officeIds->push($r->office_id));
        }

        foreach ($officeIds->unique() as $officeId) {
            self::dispatch($officeId, 'official_note_added', [
                'document_no' => $documentNo,
            ]);
        }
    }

    /**
     * Overdue threshold crossed → notify the overdue office + origin office.
     */
    public static function onOverdue(DocumentTransactionLog $receivedLog, DocumentTransaction $transaction): void
    {
        $payload = [
            'transaction_no' => $transaction->transaction_no,
            'document_no'    => $transaction->document_no,
            'subject'        => $transaction->subject,
            'office_id'      => $receivedLog->office_id,
            'office_name'    => $receivedLog->office_name,
        ];

        self::dispatch($receivedLog->office_id, 'overdue_recipient', $payload);

        if ($receivedLog->office_id !== $transaction->office_id) {
            self::dispatch($transaction->office_id, 'overdue_origin', $payload);
        }
    }

    /**
     * Force Close → notify pending recipients whose routing was halted.
     */
    public static function onForceClose(Document $document, Collection $pendingRecipients): void
    {
        foreach ($pendingRecipients as $recipient) {
            self::dispatch($recipient->office_id, 'document_force_closed', [
                'document_no' => $document->document_no,
                'subject'     => $document->subject,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal dispatch stub
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Dispatch a notification to an office.
     *
     * TODO (Phase 3+): Replace the log call with real dispatch:
     *   - Look up all users in the office
     *   - Fire Laravel Notification to each user (database + mail channels)
     *   - Or push to a websocket/broadcast channel
     */
    private static function dispatch(string $officeId, string $event, array $payload): void
    {
        \Log::info("[NotificationService] event={$event} office={$officeId}", $payload);
    }
}
