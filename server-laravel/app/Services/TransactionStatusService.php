<?php

namespace App\Services;

use App\Models\ActionLibrary;
use App\Models\Document;
use App\Models\DocumentTransaction;

/**
 * TransactionStatusService
 *
 * Single source of truth for computing and persisting DocumentTransaction.status
 * and Document.status. Call evaluate() after every action that may change state.
 *
 * RULES (from CLAUDE.md):
 *
 * FI transaction → Completed when ALL default isActive=true recipients have Received.
 * FA transaction → Completed when ALL default isActive=true recipients have a terminal action.
 *   FA terminal actions: Done | Forwarded | Returned To Sender
 *   Receive alone NEVER completes an FA transaction.
 *   Recipients deactivated by Reply (reply_is_terminal=true), Subsequent Release, or Manage
 *   Recipients are already isActive=false and are excluded from the check.
 *
 * Routing:
 *   Single     → same as Multiple with one recipient
 *   Multiple   → all active default recipients must reach terminal
 *   Sequential → all active default recipients in sequence must reach terminal;
 *                any Return halts the sequence (controller handles isActive + Returned status)
 *
 * Return to Sender:
 *   The controller is responsible for: setting all pending isActive=false, setting
 *   transaction.status = 'Returned', and calling evaluateDocumentStatus().
 *   This service will NOT override a 'Returned' status.
 *
 * Document status:
 *   evaluateDocumentStatus() is called automatically when a transaction reaches Completed.
 *   Document → Completed only when ALL its transactions are Completed.
 *   Document → Returned when ANY transaction is Returned (controller also calls this directly).
 */
class TransactionStatusService
{
    // FA terminal log statuses (Reply terminal is handled via isActive=false exclusion)
    private const FA_TERMINAL = ['Done', 'Forwarded', 'Returned To Sender'];

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Evaluate and persist the correct status for a transaction.
     * Call after every Receive, Done, Forward, or other terminal action log is written.
     *
     * Does NOT change 'Returned' status — the controller owns that transition.
     *
     * @return 'Draft'|'Processing'|'Returned'|'Completed'
     */
    public static function evaluate(string $transactionNo): string
    {
        $transaction = DocumentTransaction::with(['recipients', 'logs'])
            ->where('transaction_no', $transactionNo)
            ->lockForUpdate()
            ->first();

        if (!$transaction) {
            return 'Draft';
        }

        // Return is set explicitly by the controller; do not override it here.
        if ($transaction->status === 'Returned') {
            return 'Returned';
        }

        $newStatus = self::computeStatus($transaction);

        if ($transaction->status !== $newStatus) {
            $transaction->update(['status' => $newStatus]);

            if ($newStatus === 'Completed') {
                self::evaluateDocumentStatus($transaction->document_no);
            }
        }

        return $newStatus;
    }

    /**
     * Recompute and persist the document status from all its transactions.
     * Call directly from the controller after a Return to Sender or Force Close.
     *
     * Document status transitions:
     *   Any transaction Returned  → Document = Returned
     *   All transactions Completed → Document = Completed
     *   Otherwise                  → Document = Active
     *
     * Does NOT modify Closed documents.
     */
    public static function evaluateDocumentStatus(string $documentNo): void
    {
        $document = Document::where('document_no', $documentNo)
            ->lockForUpdate()
            ->first();

        if (!$document || $document->status === 'Closed') {
            return;
        }

        $transactions = DocumentTransaction::where('document_no', $documentNo)->get();

        if ($transactions->isEmpty()) {
            return;
        }

        $allCompleted = $transactions->every(fn($t) => $t->status === 'Completed');
        $anyReturned  = $transactions->contains(fn($t) => $t->status === 'Returned');

        if ($allCompleted) {
            $document->update(['status' => 'Completed']);
        } elseif ($anyReturned) {
            $document->update(['status' => 'Returned']);
        }
        // Active stays Active while any transaction is Processing
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────────

    private static function computeStatus(DocumentTransaction $transaction): string
    {
        $logs = $transaction->logs;

        // Must have a Released or Forwarded log to be Processing
        $isReleased = $logs->whereIn('status', ['Released', 'Forwarded'])->isNotEmpty();
        if (!$isReleased) {
            return 'Draft';
        }

        // Determine transaction type from action library
        $actionLib       = ActionLibrary::where('name', $transaction->action_type)->first();
        $isFI            = $actionLib?->type === 'FI';

        $routing = strtolower($transaction->routing);

        $isComplete = match ($routing) {
            'single'     => self::isSingleComplete($transaction, $logs, $isFI),
            'multiple'   => self::isMultipleComplete($transaction, $logs, $isFI),
            'sequential' => self::isSequentialComplete($transaction, $logs, $isFI),
            default      => false,
        };

        return $isComplete ? 'Completed' : 'Processing';
    }

    private static function isSingleComplete(DocumentTransaction $trx, $logs, bool $isFI): bool
    {
        $recipient = $trx->recipients
            ->where('recipient_type', 'default')
            ->where('isActive', true)
            ->first();

        if (!$recipient) {
            return false;
        }

        return self::officeTerminalDone($recipient->office_id, $logs, $isFI);
    }

    private static function isMultipleComplete(DocumentTransaction $trx, $logs, bool $isFI): bool
    {
        $active = $trx->recipients
            ->where('recipient_type', 'default')
            ->where('isActive', true);

        if ($active->isEmpty()) {
            return false;
        }

        return $active->every(fn($r) => self::officeTerminalDone($r->office_id, $logs, $isFI));
    }

    private static function isSequentialComplete(DocumentTransaction $trx, $logs, bool $isFI): bool
    {
        $active = $trx->recipients
            ->where('recipient_type', 'default')
            ->where('isActive', true)
            ->sortBy('sequence');

        if ($active->isEmpty()) {
            return false;
        }

        // Any Return already sets the transaction to Returned (controller-owned).
        // If we still see a Returned To Sender log here, do not mark Completed.
        if ($logs->where('status', 'Returned To Sender')->isNotEmpty()) {
            return false;
        }

        return $active->every(fn($r) => self::officeTerminalDone($r->office_id, $logs, $isFI));
    }

    /**
     * Has this office fulfilled its obligation on this transaction?
     *
     * FI: Received log present.
     * FA: Done | Forwarded | Returned To Sender log present.
     *     Recipients who replied with reply_is_terminal=true are already isActive=false
     *     and excluded before this check is called, so no special handling needed here.
     */
    private static function officeTerminalDone(string $officeId, $logs, bool $isFI): bool
    {
        $officeLogs = $logs->where('office_id', $officeId);

        if ($isFI) {
            return $officeLogs->where('status', 'Received')->isNotEmpty();
        }

        return $officeLogs->whereIn('status', self::FA_TERMINAL)->isNotEmpty();
    }
}
