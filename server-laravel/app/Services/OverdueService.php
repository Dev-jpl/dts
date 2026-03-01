<?php

namespace App\Services;

use App\Models\ActionLibrary;
use App\Models\DocumentTransaction;
use App\Models\DocumentTransactionLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * OverdueService
 *
 * Computes due dates and overdue status for FA recipients on a transaction.
 *
 * RULES (from CLAUDE.md):
 *
 * Applies to: FA recipients (default type) only.
 *   - FI recipients are never overdue.
 *   - CC/BCC recipients are never overdue.
 *
 * Overdue clock starts: at the Received log timestamp (NOT at Release).
 * If not yet received, there is no due date yet.
 *
 * Due date priority:
 *   1. transaction.due_date (explicit) → use that date (end of day)
 *   2. transaction.urgency_level       → received_at + threshold days
 *   3. document_type_library.default_urgency_level → received_at + threshold days
 *   4. System default: High → received_at + 3 days
 *
 * Thresholds:
 *   Urgent  → 1 day
 *   High    → 3 days (system default)
 *   Normal  → 5 days
 *   Routine → 7 days
 */
class OverdueService
{
    const THRESHOLDS = [
        'Urgent'  => 1,
        'High'    => 3,
        'Normal'  => 5,
        'Routine' => 7,
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get the effective due date for an office on a transaction.
     * Returns null if: not an FA transaction, not a default recipient, or not yet received.
     */
    public static function getDueDate(DocumentTransaction $transaction, string $officeId): ?Carbon
    {
        if (!self::applies($transaction, $officeId)) {
            return null;
        }

        $receivedAt = self::getReceivedAt($transaction, $officeId);
        if (!$receivedAt) {
            return null; // Clock hasn't started yet
        }

        // Priority 1: explicit due_date on the transaction
        if ($transaction->due_date) {
            return Carbon::parse($transaction->due_date)->endOfDay();
        }

        // Priority 2: urgency_level on the transaction
        if ($transaction->urgency_level) {
            return $receivedAt->copy()->addDays(self::THRESHOLDS[$transaction->urgency_level] ?? 3);
        }

        // Priority 3: document_type_library.default_urgency_level
        $docTypeUrgency = self::getDocumentTypeUrgency($transaction->document_type);
        if ($docTypeUrgency) {
            return $receivedAt->copy()->addDays(self::THRESHOLDS[$docTypeUrgency] ?? 3);
        }

        // Priority 4: system default — High (3 days)
        return $receivedAt->copy()->addDays(3);
    }

    /**
     * Check whether a Received log is overdue (spec-required signature).
     *
     * FA default recipients only. Returns false for FI or CC/BCC.
     * The transaction must have 'recipients' and 'logs' relations loaded
     * (or they will be lazy-loaded automatically by Eloquent).
     *
     * @param  DocumentTransactionLog  $receivedLog  A log with status = 'Received'.
     * @param  DocumentTransaction     $transaction  The parent transaction.
     */
    public static function isOverdue(
        DocumentTransactionLog $receivedLog,
        DocumentTransaction $transaction
    ): bool {
        return self::isOverdueForOffice($transaction, $receivedLog->office_id);
    }

    /**
     * Count the number of overdue items for an office.
     * Used by DashboardController for badge/summary counts.
     *
     * Loads all Received logs for the office with their transactions,
     * then evaluates each via isOverdue().
     */
    public static function countOverdueForOffice(string $officeId): int
    {
        $logs = DocumentTransactionLog::where('status', 'Received')
            ->where('office_id', $officeId)
            ->with([
                'transaction',
                'transaction.recipients',
                'transaction.logs',
            ])
            ->get();

        $count = 0;

        foreach ($logs as $log) {
            if ($log->transaction && self::isOverdue($log, $log->transaction)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Whether this office is currently overdue on this transaction.
     * Used internally and by getDueDate / getTransactionOverdueMap callers.
     */
    public static function isOverdueForOffice(DocumentTransaction $transaction, string $officeId): bool
    {
        // Already completed their obligation — not overdue
        if (self::hasCompletedObligation($transaction, $officeId)) {
            return false;
        }

        $dueDate = self::getDueDate($transaction, $officeId);
        if (!$dueDate) {
            return false;
        }

        return now()->isAfter($dueDate);
    }

    /**
     * Days remaining until due (negative = overdue by N days).
     * Returns null if not applicable or not yet received.
     */
    public static function daysUntilDue(DocumentTransaction $transaction, string $officeId): ?int
    {
        $dueDate = self::getDueDate($transaction, $officeId);
        if (!$dueDate) {
            return null;
        }

        return (int) now()->diffInDays($dueDate, false);
    }

    /**
     * Get overdue info for all FA default recipients of a transaction.
     * Returns array keyed by office_id.
     */
    public static function getTransactionOverdueMap(DocumentTransaction $transaction): array
    {
        $actionLib = ActionLibrary::where('name', $transaction->action_type)->first();
        if (!$actionLib || $actionLib->type === 'FI') {
            return [];
        }

        $result = [];

        $defaultRecipients = $transaction->recipients
            ->where('recipient_type', 'default')
            ->where('isActive', true);

        foreach ($defaultRecipients as $recipient) {
            $dueDate = self::getDueDate($transaction, $recipient->office_id);
            $result[$recipient->office_id] = [
                'office_id'   => $recipient->office_id,
                'office_name' => $recipient->office_name,
                'due_date'    => $dueDate?->toDateTimeString(),
                'is_overdue'  => $dueDate ? self::isOverdueForOffice($transaction, $recipient->office_id) : false,
                'days_until'  => $dueDate ? (int) now()->diffInDays($dueDate, false) : null,
            ];
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Overdue tracking only applies to FA default recipients.
     */
    private static function applies(DocumentTransaction $transaction, string $officeId): bool
    {
        $actionLib = ActionLibrary::where('name', $transaction->action_type)->first();
        if (!$actionLib || $actionLib->type === 'FI') {
            return false;
        }

        $isDefaultRecipient = $transaction->recipients
            ->where('office_id', $officeId)
            ->where('recipient_type', 'default')
            ->isNotEmpty();

        return $isDefaultRecipient;
    }

    /**
     * Get the Received log timestamp for this office (the overdue clock start).
     */
    private static function getReceivedAt(DocumentTransaction $transaction, string $officeId): ?Carbon
    {
        $log = $transaction->logs
            ->where('office_id', $officeId)
            ->where('status', 'Received')
            ->sortBy('created_at')
            ->first();

        return $log ? Carbon::parse($log->created_at) : null;
    }

    /**
     * Has the office already completed their FA obligation?
     * True if they have Done, Forwarded, or Returned To Sender logs.
     */
    private static function hasCompletedObligation(DocumentTransaction $transaction, string $officeId): bool
    {
        return $transaction->logs
            ->where('office_id', $officeId)
            ->whereIn('status', ['Done', 'Forwarded', 'Returned To Sender'])
            ->isNotEmpty();
    }

    /**
     * Look up the default urgency level from the document_type_library.
     */
    private static function getDocumentTypeUrgency(string $documentType): ?string
    {
        $row = DB::table('document_type_library')
            ->where('name', $documentType)
            ->value('default_urgency_level');

        return $row ?: null;
    }
}
