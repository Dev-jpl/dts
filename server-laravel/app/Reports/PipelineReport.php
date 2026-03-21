<?php

namespace App\Reports;

use App\Services\OverdueService;
use Illuminate\Support\Facades\DB;

class PipelineReport
{
    /**
     * Document pipeline: status summary, overdue list, age distribution.
     *
     * @param array|null $officeIds  null = all
     * @param string|null $status   filter to one status
     */
    public static function getData(?array $officeIds, ?string $status = null): array
    {
        // ── Status summary ─────────────────────────────────────────────────────
        $summaryQuery = DB::table('documents')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status');

        if ($officeIds !== null) {
            $summaryQuery->whereIn('office_id', $officeIds);
        }
        if ($status) {
            $summaryQuery->where('status', $status);
        }

        $summaryRows = $summaryQuery->get();
        $summary = [];
        foreach (['Draft', 'Active', 'Returned', 'Completed', 'Closed'] as $s) {
            $summary[$s] = 0;
        }
        foreach ($summaryRows as $r) {
            $summary[$r->status] = (int) $r->count;
        }

        // ── Overdue list (Active documents only, FA type, past due) ───────────
        // Load FA-type transactions in Processing status with isActive recipients
        $overdueQuery = DB::table('document_transaction_logs as received')
            ->join('document_transactions as trx', 'trx.transaction_no', '=', 'received.transaction_no')
            ->join('documents as doc', 'doc.document_no', '=', 'trx.document_no')
            ->join('action_library as al', 'al.name', '=', 'trx.action_type')
            ->join('document_recipients as dr', function ($j) {
                $j->on('dr.transaction_no', '=', 'trx.transaction_no')
                  ->on('dr.office_id', '=', 'received.office_id')
                  ->where('dr.isActive', true)
                  ->where('dr.recipient_type', 'default');
            })
            ->where('received.status', 'Received')
            ->where('al.type', 'FA')
            ->where('trx.status', 'Processing')
            ->whereNotExists(function ($q) {
                $q->from('document_transaction_logs as terminal')
                  ->whereColumn('terminal.transaction_no', 'received.transaction_no')
                  ->whereColumn('terminal.office_id', 'received.office_id')
                  ->whereIn('terminal.status', ['Done', 'Forwarded', 'Returned To Sender']);
            })
            ->select(
                'doc.document_no',
                'doc.status as doc_status',
                'trx.transaction_no',
                'trx.subject',
                'trx.document_type',
                'trx.urgency_level',
                'trx.due_date',
                'received.office_id',
                'received.office_name',
                'received.created_at as received_at'
            );

        if ($officeIds !== null) {
            $overdueQuery->whereIn('received.office_id', $officeIds);
        }

        $potentialOverdue = $overdueQuery->get();

        $overdue = [];
        foreach ($potentialOverdue as $item) {
            $receivedAt = \Carbon\Carbon::parse($item->received_at);

            $dueDate = $item->due_date
                ? \Carbon\Carbon::parse($item->due_date)
                : self::calcDueDate($receivedAt, $item->urgency_level);

            if ($dueDate && now()->gt($dueDate)) {
                $daysOverdue = (int) now()->diffInDays($dueDate);
                $overdue[] = [
                    'document_no'    => $item->document_no,
                    'transaction_no' => $item->transaction_no,
                    'subject'        => $item->subject,
                    'document_type'  => $item->document_type,
                    'urgency_level'  => $item->urgency_level,
                    'office_name'    => $item->office_name,
                    'received_at'    => $item->received_at,
                    'due_date'       => $dueDate->toDateString(),
                    'days_overdue'   => $daysOverdue,
                ];
            }
        }

        // Sort most overdue first
        usort($overdue, fn($a, $b) => $b['days_overdue'] <=> $a['days_overdue']);

        // ── Age distribution (Active documents by days since first release) ────
        // Use a CTE: compute MIN(released_at) per document first, then bucket.
        // Aggregates cannot appear in GROUP BY in PostgreSQL.
        $officeFilter = $officeIds !== null
            ? 'AND doc.office_id IN (' . implode(',', array_fill(0, count($officeIds), '?')) . ')'
            : '';

        $ageRows = DB::select("
            WITH first_release AS (
                SELECT
                    doc.document_no,
                    MIN(rel.created_at) AS first_released_at
                FROM documents doc
                INNER JOIN document_transactions trx
                    ON trx.document_no = doc.document_no
                   AND trx.status = 'Processing'
                INNER JOIN document_transaction_logs rel
                    ON rel.transaction_no = trx.transaction_no
                   AND rel.status = 'Released'
                WHERE doc.status = 'Active'
                {$officeFilter}
                GROUP BY doc.document_no
            )
            SELECT
                CASE
                    WHEN EXTRACT(DAY FROM NOW() - first_released_at) < 7  THEN 'under_7'
                    WHEN EXTRACT(DAY FROM NOW() - first_released_at) < 30 THEN '7_to_30'
                    WHEN EXTRACT(DAY FROM NOW() - first_released_at) < 90 THEN '30_to_90'
                    ELSE 'over_90'
                END AS age_bucket,
                COUNT(*) AS count
            FROM first_release
            GROUP BY age_bucket
        ", $officeIds ?? []);

        $ageBuckets = [
            'under_7'   => 0,
            '7_to_30'   => 0,
            '30_to_90'  => 0,
            'over_90'   => 0,
        ];
        foreach ($ageRows as $r) {
            $ageBuckets[$r->age_bucket] = (int) $r->count;
        }

        return [
            'summary'   => $summary,
            'overdue'   => $overdue,
            'aged'      => $ageBuckets,
        ];
    }

    private static function calcDueDate(\Carbon\Carbon $receivedAt, ?string $urgencyLevel): ?\Carbon\Carbon
    {
        $days = match ($urgencyLevel) {
            'Urgent'  => 1,
            'High'    => 3,
            'Normal'  => 5,
            'Routine' => 7,
            default   => 3,
        };
        return $receivedAt->copy()->addDays($days);
    }

    public static function columns(): array
    {
        return ['Document No', 'Subject', 'Type', 'Urgency', 'Office', 'Received At', 'Due Date', 'Days Overdue'];
    }

    public static function toRows(array $data): array
    {
        return array_map(fn($row) => [
            $row['document_no'],
            $row['subject'],
            $row['document_type'],
            $row['urgency_level'],
            $row['office_name'],
            $row['received_at'],
            $row['due_date'],
            $row['days_overdue'],
        ], $data['overdue']);
    }
}
