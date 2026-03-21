<?php

namespace App\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Individual Turnaround Report.
 *
 * Metrics: avg hours from Received → terminal action,
 * broken down by action type, document type, and trend over time.
 */
class TurnaroundReport
{
    public static function getData(?array $officeIds, string $periodStart, string $periodEnd): array
    {
        // ── Base: Received → terminal action pairs ────────────────────────────
        $baseQuery = fn() => DB::table('document_transaction_logs as r')
            ->join('document_transaction_logs as t', function ($j) {
                $j->on('t.transaction_no', '=', 'r.transaction_no')
                  ->on('t.office_id', '=', 'r.office_id')
                  ->whereIn('t.status', ['Done', 'Forwarded', 'Returned To Sender']);
            })
            ->join('document_transactions as trx', 'trx.transaction_no', '=', 'r.transaction_no')
            ->where('r.status', 'Received')
            ->whereBetween('t.created_at', [$periodStart, $periodEnd])
            ->when($officeIds !== null, fn($q) => $q->whereIn('r.office_id', $officeIds));

        // ── Overall avg turnaround ────────────────────────────────────────────
        $overall = $baseQuery()
            ->selectRaw("
                AVG(EXTRACT(EPOCH FROM (t.created_at - r.created_at)) / 3600) AS avg_hours,
                COUNT(*) AS completed_count
            ")
            ->first();

        // ── By action type ────────────────────────────────────────────────────
        $byActionType = $baseQuery()
            ->selectRaw("
                trx.action_type,
                AVG(EXTRACT(EPOCH FROM (t.created_at - r.created_at)) / 3600) AS avg_hours,
                COUNT(*) AS completed_count
            ")
            ->groupBy('trx.action_type')
            ->orderByDesc('completed_count')
            ->get()
            ->map(fn($r) => [
                'action_type'     => $r->action_type,
                'avg_hours'       => $r->avg_hours ? round($r->avg_hours, 1) : null,
                'completed_count' => (int) $r->completed_count,
            ])
            ->toArray();

        // ── By document type ──────────────────────────────────────────────────
        $byDocType = $baseQuery()
            ->selectRaw("
                trx.document_type,
                AVG(EXTRACT(EPOCH FROM (t.created_at - r.created_at)) / 3600) AS avg_hours,
                COUNT(*) AS completed_count
            ")
            ->groupBy('trx.document_type')
            ->orderByDesc('completed_count')
            ->get()
            ->map(fn($r) => [
                'document_type'   => $r->document_type,
                'avg_hours'       => $r->avg_hours ? round($r->avg_hours, 1) : null,
                'completed_count' => (int) $r->completed_count,
            ])
            ->toArray();

        // ── Monthly trend (within the period) ─────────────────────────────────
        $trend = $baseQuery()
            ->selectRaw("
                TO_CHAR(t.created_at, 'YYYY-MM') AS period_label,
                AVG(EXTRACT(EPOCH FROM (t.created_at - r.created_at)) / 3600) AS avg_hours,
                COUNT(*) AS completed_count
            ")
            ->groupBy('period_label')
            ->orderBy('period_label')
            ->get()
            ->map(fn($r) => [
                'period'          => $r->period_label,
                'avg_hours'       => $r->avg_hours ? round($r->avg_hours, 1) : null,
                'completed_count' => (int) $r->completed_count,
            ])
            ->toArray();

        // ── By urgency level ──────────────────────────────────────────────────
        $byUrgency = $baseQuery()
            ->selectRaw("
                trx.urgency_level,
                AVG(EXTRACT(EPOCH FROM (t.created_at - r.created_at)) / 3600) AS avg_hours,
                COUNT(*) AS completed_count
            ")
            ->groupBy('trx.urgency_level')
            ->orderByRaw("
                CASE trx.urgency_level
                    WHEN 'Urgent'  THEN 1
                    WHEN 'High'    THEN 2
                    WHEN 'Normal'  THEN 3
                    WHEN 'Routine' THEN 4
                    ELSE 5
                END
            ")
            ->get()
            ->map(fn($r) => [
                'urgency_level'   => $r->urgency_level,
                'avg_hours'       => $r->avg_hours ? round($r->avg_hours, 1) : null,
                'completed_count' => (int) $r->completed_count,
            ])
            ->toArray();

        return [
            'overall' => [
                'avg_hours'       => $overall?->avg_hours ? round($overall->avg_hours, 1) : null,
                'completed_count' => (int) ($overall?->completed_count ?? 0),
            ],
            'by_action_type'   => $byActionType,
            'by_document_type' => $byDocType,
            'by_urgency'       => $byUrgency,
            'trend'            => $trend,
        ];
    }

    public static function columns(): array
    {
        return ['Breakdown', 'Category', 'Avg. Turnaround (hrs)', 'Completed Count'];
    }

    public static function toRows(array $data): array
    {
        $rows = [];

        foreach ($data['by_action_type'] as $r) {
            $rows[] = ['By Action Type', $r['action_type'], $r['avg_hours'] ?? 'N/A', $r['completed_count']];
        }
        foreach ($data['by_document_type'] as $r) {
            $rows[] = ['By Document Type', $r['document_type'], $r['avg_hours'] ?? 'N/A', $r['completed_count']];
        }
        foreach ($data['by_urgency'] as $r) {
            $rows[] = ['By Urgency', $r['urgency_level'], $r['avg_hours'] ?? 'N/A', $r['completed_count']];
        }

        return $rows;
    }
}
