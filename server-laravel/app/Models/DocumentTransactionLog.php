<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DocumentTransactionLog extends Model
{
    use HasFactory;

    protected $table = 'document_transaction_logs';

    protected $fillable = [
        'document_no',
        'transaction_no',
        'office_id',
        'office_name',
        'routed_office_id',
        'routed_office_name',
        'status',
        'action_taken',
        'activity',
        'remarks',
        'reason',
        'assigned_personnel_id',
        'assigned_personnel_name',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function office()
    {
        return $this->belongsTo(OfficeLibrary::class, 'office_id');
    }

    public function routedOffice()
    {
        return $this->belongsTo(OfficeLibrary::class, 'routed_office_id');
    }

    public function personnel()
    {
        return $this->belongsTo(User::class, 'assigned_personnel_id', 'id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_no', 'document_no');
    }

    public function transaction()
    {
        return $this->belongsTo(DocumentTransaction::class, 'transaction_no', 'transaction_no');
    }

    // -------------------------------------------------------------------------
    // Incoming Module Scopes
    // -------------------------------------------------------------------------

    // Terminal action statuses (office has fulfilled its obligation)
    private const TERMINAL = ['Done', 'Forwarded', 'Returned To Sender'];

    /**
     * Helper: Make results distinct per transaction (PostgreSQL DISTINCT ON).
     * Returns the most recent log per transaction.
     */
    public function scopeDistinctPerTransaction(Builder $query): Builder
    {
        return $query->select(DB::raw('DISTINCT ON (document_transaction_logs.transaction_no) document_transaction_logs.*'))
            ->orderBy('document_transaction_logs.transaction_no')
            ->orderBy('document_transaction_logs.created_at', 'desc');
    }

    /**
     * Check if office has performed a subsequent release on this transaction.
     * A subsequent release is a Released log where office_id = the checking office
     * AND routed_office_id is not null (meaning they released to someone else).
     */
    private static function hasSubsequentRelease($sub, string $officeId): void
    {
        $sub->selectRaw(1)
            ->from('document_transaction_logs as dtl_subrel')
            ->whereColumn('dtl_subrel.transaction_no', 'document_transaction_logs.transaction_no')
            ->where('dtl_subrel.office_id', $officeId)
            ->where('dtl_subrel.status', 'Released')
            ->whereNotNull('dtl_subrel.routed_office_id');
    }

    /**
     * All Incoming — documents released TO this office OR already acted on by this office.
     */
    public function scopeIncomingForOffice(Builder $query, string $officeId): Builder
    {
        return $query->where(function (Builder $q) use ($officeId) {
            // Released/routed to this office
            $q->where(function (Builder $q2) use ($officeId) {
                $q2->where('routed_office_id', $officeId)
                    ->where('status', 'Released');
            })
            // Actions performed by this office (including Closed logs)
            ->orWhere(function (Builder $q2) use ($officeId) {
                $q2->where('office_id', $officeId)
                    ->whereIn('status', array_merge(['Received', 'Closed'], self::TERMINAL));
            })
            // Closed documents where this office was involved (any log status)
            ->orWhere(function (Builder $q2) use ($officeId) {
                $q2->where(function (Builder $q3) use ($officeId) {
                    $q3->where('office_id', $officeId)
                        ->orWhere('routed_office_id', $officeId);
                })->whereHas('transaction.document', function ($q3) {
                    $q3->where('status', 'Closed');
                });
            });
        });
    }

    /**
     * For Action — released to this office but NOT YET RECEIVED.
     * Shows documents awaiting receipt only (not in progress).
     */
    public function scopeForAction(Builder $query, string $officeId): Builder
    {
        return $query->where('document_transaction_logs.routed_office_id', $officeId)
            ->where('document_transaction_logs.status', 'Released')
            // Exclude if already received
            ->whereNotExists(function ($sub) use ($officeId) {
                $sub->selectRaw(1)
                    ->from('document_transaction_logs as dtl_recv')
                    ->whereColumn('dtl_recv.transaction_no', 'document_transaction_logs.transaction_no')
                    ->where('dtl_recv.office_id', $officeId)
                    ->where('dtl_recv.status', 'Received');
            })
            // Exclude if terminal action taken
            ->whereNotExists(function ($sub) use ($officeId) {
                $sub->selectRaw(1)
                    ->from('document_transaction_logs as dtl_action')
                    ->whereColumn('dtl_action.transaction_no', 'document_transaction_logs.transaction_no')
                    ->where('dtl_action.office_id', $officeId)
                    ->whereIn('dtl_action.status', self::TERMINAL);
            })
            ->whereNotExists(function ($sub) use ($officeId) {
                // Also exclude if this office has done a subsequent release
                self::hasSubsequentRelease($sub, $officeId);
            });
    }

    /**
     * Actioned — legacy alias for backward compat (same as completedByOffice).
     */
    public function scopeActioned(Builder $query, string $officeId): Builder
    {
        return $this->scopeCompletedByOffice($query, $officeId);
    }

    /**
     * In Progress — this office has received the document but not yet taken a terminal action.
     */
    public function scopeInProgress(Builder $query, string $officeId): Builder
    {
        return $query->where('office_id', $officeId)
            ->where('status', 'Received')
            ->whereNotExists(function ($sub) use ($officeId) {
                $sub->selectRaw(1)
                    ->from('document_transaction_logs as dtl_check')
                    ->whereColumn('dtl_check.transaction_no', 'document_transaction_logs.transaction_no')
                    ->where('dtl_check.office_id', $officeId)
                    ->whereIn('dtl_check.status', self::TERMINAL);
            })
            ->whereNotExists(function ($sub) use ($officeId) {
                // Also exclude if this office has done a subsequent release
                self::hasSubsequentRelease($sub, $officeId);
            });
    }

    /**
     * Completed — this office has taken a terminal action (Done / Forwarded / Returned To Sender)
     * OR has performed a subsequent release.
     *
     * IMPORTANT: Only includes logs where the office was a RECIPIENT (has Received log),
     * not the originator. This prevents origin office's initial release from appearing.
     */
    public function scopeCompletedByOffice(Builder $query, string $officeId): Builder
    {
        return $query->where('office_id', $officeId)
            ->where(function (Builder $q) {
                // Traditional terminal actions
                $q->whereIn('status', self::TERMINAL)
                // Or subsequent release (Released with routed_office_id set)
                ->orWhere(function (Builder $subQ) {
                    $subQ->where('status', 'Released')
                         ->whereNotNull('routed_office_id');
                });
            })
            // Guard: office must have received this document (proves they were a recipient)
            ->whereExists(function ($sub) use ($officeId) {
                $sub->selectRaw(1)
                    ->from('document_transaction_logs as dtl_recv')
                    ->whereColumn('dtl_recv.transaction_no', 'document_transaction_logs.transaction_no')
                    ->where('dtl_recv.office_id', $officeId)
                    ->where('dtl_recv.status', 'Received');
            });
    }

    /**
     * Closed — the document linked to this log has been closed.
     */
    public function scopeClosedForOffice(Builder $query, string $officeId): Builder
    {
        return $query->where(function (Builder $q) use ($officeId) {
            $q->where('office_id', $officeId)
              ->orWhere('routed_office_id', $officeId);
        })->whereHas('transaction.document', function ($q) {
            $q->where('status', 'Closed');
        });
    }

    /**
     * Overdue — received by this office but no terminal action within $days.
     * Clock starts at: Received log created_at.
     * Uses PostgreSQL interval syntax.
     */
    public function scopeOverdue(Builder $query, string $officeId, int $days = 3): Builder
    {
        return $query->where('office_id', $officeId)
            ->where('status', 'Received')
            ->whereRaw("created_at < NOW() - INTERVAL '{$days} days'")
            ->whereNotExists(function ($sub) use ($officeId) {
                $sub->selectRaw(1)
                    ->from('document_transaction_logs as dtl_check')
                    ->whereColumn('dtl_check.transaction_no', 'document_transaction_logs.transaction_no')
                    ->where('dtl_check.office_id', $officeId)
                    ->whereIn('dtl_check.status', self::TERMINAL);
            })
            ->whereNotExists(function ($sub) use ($officeId) {
                // Also exclude if this office has done a subsequent release
                self::hasSubsequentRelease($sub, $officeId);
            });
    }
}
