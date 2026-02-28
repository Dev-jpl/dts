<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentNote;
use App\Models\DocumentRecipient;
use App\Models\DocumentTransaction;
use App\Models\User;
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
 *   Initial Release        → All recipients including CC/BCC
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
     * Initial Release → notify all recipients (default, CC, BCC).
     */
    public static function onInitialRelease(DocumentTransaction $transaction): void
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
            'transaction_no'    => $transaction->transaction_no,
            'document_no'       => $transaction->document_no,
            'subject'           => $transaction->subject,
            'from_office'       => $transaction->office_name,
            'target_office_id'  => $targetOfficeId,
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
    public static function onReceive(DocumentTransaction $transaction, string $receivingOfficeId, string $receivingOfficeName, string $recipientType): void
    {
        if ($recipientType !== 'default') {
            return; // CC/BCC: no notification
        }

        self::dispatch($transaction->office_id, 'document_received', [
            'transaction_no'       => $transaction->transaction_no,
            'document_no'          => $transaction->document_no,
            'subject'              => $transaction->subject,
            'receiving_office_id'  => $receivingOfficeId,
            'receiving_office_name' => $receivingOfficeName,
        ]);
    }

    /**
     * Sequential routing: the next step just became active → notify the next recipient.
     */
    public static function onSequentialStepActivated(DocumentTransaction $transaction, string $nextOfficeId, string $nextOfficeName): void
    {
        self::dispatch($nextOfficeId, 'sequential_step_activated', [
            'transaction_no'    => $transaction->transaction_no,
            'document_no'       => $transaction->document_no,
            'subject'           => $transaction->subject,
            'action_type'       => $transaction->action_type,
            'from_office'       => $transaction->office_name,
            'next_office_id'    => $nextOfficeId,
            'next_office_name'  => $nextOfficeName,
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
     */
    public static function onForward(DocumentTransaction $transaction, string $targetOfficeId, string $targetOfficeName): void
    {
        self::dispatch($targetOfficeId, 'document_forwarded', [
            'transaction_no'     => $transaction->transaction_no,
            'document_no'        => $transaction->document_no,
            'subject'            => $transaction->subject,
            'action_type'        => $transaction->action_type,
            'from_office'        => $transaction->office_name,
            'target_office_id'   => $targetOfficeId,
            'target_office_name' => $targetOfficeName,
        ]);
    }

    /**
     * Return to Sender → notify the origin office + all halted (now-inactive) offices.
     */
    public static function onReturnToSender(
        DocumentTransaction $transaction,
        string $returningOfficeId,
        string $returningOfficeName,
        string $reason,
        ?string $remarks,
        Collection $haltedOffices  // collection of DocumentRecipient
    ): void {
        $payload = [
            'transaction_no'        => $transaction->transaction_no,
            'document_no'           => $transaction->document_no,
            'subject'               => $transaction->subject,
            'returning_office_id'   => $returningOfficeId,
            'returning_office_name' => $returningOfficeName,
            'reason'                => $reason,
            'remarks'               => $remarks,
        ];

        // Notify origin
        self::dispatch($transaction->office_id, 'returned_to_sender', $payload);

        // Notify each halted office
        foreach ($haltedOffices as $recipient) {
            if ($recipient->office_id === $transaction->office_id) {
                continue; // already notified above
            }
            self::dispatch($recipient->office_id, 'routing_halted', $payload);
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
    // Notes / Close
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Official Note added → notify all active participants.
     * Active participants = origin + isActive=true default recipients.
     */
    public static function onOfficialNoteAdded(Document $document, DocumentNote $note): void
    {
        $officeIds = collect([$document->office_id]);

        // Collect all active default recipients across all active transactions
        foreach ($document->transactions as $transaction) {
            $transaction->recipients
                ->where('recipient_type', 'default')
                ->where('isActive', true)
                ->each(fn($r) => $officeIds->push($r->office_id));
        }

        foreach ($officeIds->unique() as $officeId) {
            self::dispatch($officeId, 'official_note_added', [
                'document_no'    => $document->document_no,
                'subject'        => $document->subject,
                'note_excerpt'   => mb_substr($note->note, 0, 100),
                'from_office'    => $note->office_name,
            ]);
        }
    }

    /**
     * Overdue threshold crossed → notify the overdue office + origin office.
     */
    public static function onOverdueThreshold(DocumentTransaction $transaction, string $officeId, string $officeName, int $daysPastDue): void
    {
        $payload = [
            'transaction_no' => $transaction->transaction_no,
            'document_no'    => $transaction->document_no,
            'subject'        => $transaction->subject,
            'office_id'      => $officeId,
            'office_name'    => $officeName,
            'days_past_due'  => $daysPastDue,
        ];

        self::dispatch($officeId, 'overdue_recipient', $payload);

        if ($officeId !== $transaction->office_id) {
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
