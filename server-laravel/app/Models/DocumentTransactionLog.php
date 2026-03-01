<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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
     * All Incoming — documents released TO this office OR already acted on by this office.
     */
    public function scopeIncomingForOffice(Builder $query, string $officeId): Builder
    {
        return $query->where(function (Builder $q) use ($officeId) {
            // Released/routed to this office
            $q->where('routed_office_id', $officeId)
                ->where('status', 'Released');
        })->orWhere(function (Builder $q) use ($officeId) {
            // Actions performed by this office
            $q->where('office_id', $officeId)
                ->whereIn('status', array_merge(['Received'], self::TERMINAL));
        });
    }

    /**
     * For Action — released to this office with no terminal action yet.
     * Covers both "Awaiting Receipt" (not received) and "In Progress" (received, not done).
     */
    public function scopeForAction(Builder $query, string $officeId): Builder
    {
        return $query->where('routed_office_id', $officeId)
            ->where('status', 'Released')
            ->whereNotExists(function ($sub) use ($officeId) {
                $sub->selectRaw(1)
                    ->from('document_transaction_logs as dtl_action')
                    ->whereColumn('dtl_action.transaction_no', 'document_transaction_logs.transaction_no')
                    ->where('dtl_action.office_id', $officeId)
                    ->whereIn('dtl_action.status', self::TERMINAL);
            });
    }

    /**
     * Actioned — legacy alias for backward compat (same as completedByOffice).
     */
    public function scopeActioned(Builder $query, string $officeId): Builder
    {
        return $query->where('office_id', $officeId)
            ->whereIn('status', self::TERMINAL);
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
            });
    }

    /**
     * Completed — this office has taken a terminal action (Done / Forwarded / Returned To Sender).
     */
    public function scopeCompletedByOffice(Builder $query, string $officeId): Builder
    {
        return $query->where('office_id', $officeId)
            ->whereIn('status', self::TERMINAL);
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
            });
    }
}
