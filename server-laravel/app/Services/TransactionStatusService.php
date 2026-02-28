<?php

namespace App\Services;

use App\Models\DocumentTransaction;
use App\Models\DocumentRecipient;
use App\Models\DocumentTransactionLog;

/**
 * TransactionStatusService
 *
 * Single source of truth for computing and updating DocumentTransaction.status
 * based on routing type and log history.
 *
 * STATUS RULES:
 *  Draft       — created, not yet released
 *  Processing  — released (routing is active)
 *  Completed   — all required recipients have Received (or Returned To Sender)
 *
 * ROUTING MATRIX:
 *  Single     → Completed when the one recipient Receives
 *  Multiple   → Completed when ALL default recipients have Received or Returned
 *  Sequential → Completed when the LAST recipient in sequence has Received
 *
 * FORWARD:
 *  A Forward re-opens activity. The forwarded-to recipient(s) must Receive
 *  before Completed is reached again. Same routing rules apply to the forward leg.
 */
class TransactionStatusService
{
    /**
     * Evaluate and persist the correct status for the transaction.
     * Call this after every Receive, Forward, or Return to Sender log is created.
     *
     * @return 'Draft'|'Processing'|'Completed'  the new status
     */
    public static function evaluate(string $transactionNo): string
    {
        $transaction = DocumentTransaction::with(['recipients', 'logs'])
            ->where('transaction_no', $transactionNo)
            ->lockForUpdate()   // prevent race conditions on concurrent receives
            ->first();

        if (!$transaction) {
            return 'Draft';
        }

        $routing  = strtolower($transaction->routing);  // single | multiple | sequential
        $logs     = $transaction->logs;
        $newStatus = self::computeStatus($routing, $transaction, $logs);

        if ($transaction->status !== $newStatus) {
            $transaction->update(['status' => $newStatus]);
        }

        return $newStatus;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────────

    private static function computeStatus(
        string $routing,
        DocumentTransaction $transaction,
        $logs
    ): string {
        // If not yet released, stay Draft
        $isReleased = $logs->where('status', 'Released')->isNotEmpty()
            || $logs->where('status', 'Forwarded')->isNotEmpty();

        if (!$isReleased) {
            return 'Draft';
        }

        // Check completion based on routing
        $isComplete = match ($routing) {
            'single'     => self::isSingleComplete($transaction, $logs),
            'multiple'   => self::isMultipleComplete($transaction, $logs),
            'sequential' => self::isSequentialComplete($transaction, $logs),
            default      => false,
        };

        return $isComplete ? 'Completed' : 'Processing';
    }

    /**
     * Single routing: the one default recipient must have Received or Returned.
     */
    private static function isSingleComplete(DocumentTransaction $trx, $logs): bool
    {
        // Only look at ACTIVE recipients
        $recipient = $trx->recipients
            ->where('recipient_type', 'default')
            ->where('isActive', true)   // ← ADD THIS
            ->first();

        if (!$recipient) return false;

        return self::officeDone($recipient->office_id, $logs);
    }

    /**
     * Multiple routing: ALL default recipients must have Received or Returned.
     */
    private static function isMultipleComplete(DocumentTransaction $trx, $logs): bool
    {
        $defaultRecipients = $trx->recipients
            ->where('recipient_type', 'default')
            ->where('isActive', true);   // ← ADD THIS

        if ($defaultRecipients->isEmpty()) return false;

        return $defaultRecipients->every(
            fn($r) => self::officeDone($r->office_id, $logs)
        );
    }


    /**
     * Sequential routing: the LAST recipient in the sequence must have Received.
     * If any step Returned, we do NOT complete — the sequence is broken.
     */
    private static function isSequentialComplete(DocumentTransaction $trx, $logs): bool
    {
        $defaultRecipients = $trx->recipients
            ->where('recipient_type', 'default')
            ->where('isActive', true)    // ← ADD THIS
            ->sortBy('sequence');

        if ($defaultRecipients->isEmpty()) return false;

        $anyReturned = $logs->where('status', 'Returned To Sender')->isNotEmpty();
        if ($anyReturned) return false;

        return $defaultRecipients->every(
            fn($r) => $logs->where('status', 'Received')
                ->where('office_id', $r->office_id)
                ->isNotEmpty()
        );
    }


    /**
     * An office is "done" if they have Received OR Returned To Sender.
     */
    private static function officeDone(string $officeId, $logs): bool
    {
        return $logs->where('office_id', $officeId)
            ->whereIn('status', ['Received', 'Returned To Sender'])
            ->isNotEmpty();
    }
}
