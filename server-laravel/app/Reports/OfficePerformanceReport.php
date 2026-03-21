<?php

namespace App\Reports;

use App\Services\OverdueService;
use Illuminate\Support\Facades\DB;

class OfficePerformanceReport
{
    /**
     * Build performance metrics per office within the given date range.
     *
     * @param array|null $officeIds   null = all offices
     * @param string     $periodStart ISO 8601
     * @param string     $periodEnd   ISO 8601
     * @return array
     */
    public static function getData(?array $officeIds, string $periodStart, string $periodEnd): array
    {
        $officeQuery = DB::table('office_libraries')->select('id', 'office_name');
        if ($officeIds !== null) {
            $officeQuery->whereIn('id', $officeIds);
        }
        $offices = $officeQuery->orderBy('office_name')->get();

        $rows = [];

        foreach ($offices as $office) {
            $oid = $office->id;

            // ── Received count ─────────────────────────────────────────────────
            $receivedCount = DB::table('document_transaction_logs')
                ->where('office_id', $oid)
                ->where('status', 'Received')
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->count();

            // ── Avg time to receive (hours: Released → Received) ───────────────
            $avgReceive = DB::selectOne("
                SELECT AVG(EXTRACT(EPOCH FROM (r.created_at - rel.created_at)) / 3600) AS avg_hours
                FROM document_transaction_logs r
                JOIN document_transaction_logs rel
                     ON rel.transaction_no = r.transaction_no
                    AND rel.status = 'Released'
                WHERE r.office_id = ?
                  AND r.status = 'Received'
                  AND r.created_at BETWEEN ? AND ?
            ", [$oid, $periodStart, $periodEnd]);

            // ── Avg time to complete (hours: Received → terminal action) ────────
            $avgComplete = DB::selectOne("
                SELECT AVG(EXTRACT(EPOCH FROM (t.created_at - r.created_at)) / 3600) AS avg_hours
                FROM document_transaction_logs r
                JOIN document_transaction_logs t
                     ON t.transaction_no = r.transaction_no
                    AND t.office_id = r.office_id
                    AND t.status IN ('Done', 'Forwarded', 'Returned To Sender')
                WHERE r.office_id = ?
                  AND r.status = 'Received'
                  AND r.created_at BETWEEN ? AND ?
            ", [$oid, $periodStart, $periodEnd]);

            // ── Return rate ────────────────────────────────────────────────────
            $returnedCount = DB::table('document_transaction_logs')
                ->where('office_id', $oid)
                ->where('status', 'Returned To Sender')
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->count();

            $returnRate = $receivedCount > 0
                ? round(($returnedCount / $receivedCount) * 100, 1)
                : 0;

            // ── Return reasons ─────────────────────────────────────────────────
            $returnReasons = DB::table('document_transaction_logs')
                ->where('office_id', $oid)
                ->where('status', 'Returned To Sender')
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->whereNotNull('reason')
                ->selectRaw('reason, COUNT(*) as count')
                ->groupBy('reason')
                ->orderByDesc('count')
                ->get()
                ->map(fn($r) => ['reason' => $r->reason, 'count' => $r->count])
                ->toArray();

            // ── On-time rate (completed within due/urgency threshold) ───────────
            // Load terminal action logs in period and check against urgency threshold
            $terminalLogs = DB::table('document_transaction_logs as t')
                ->join('document_transaction_logs as r', function ($j) use ($oid) {
                    $j->on('r.transaction_no', '=', 't.transaction_no')
                      ->where('r.office_id', $oid)
                      ->where('r.status', 'Received');
                })
                ->join('document_transactions as trx', 'trx.transaction_no', '=', 't.transaction_no')
                ->where('t.office_id', $oid)
                ->whereIn('t.status', ['Done', 'Forwarded', 'Returned To Sender'])
                ->whereBetween('t.created_at', [$periodStart, $periodEnd])
                ->selectRaw("
                    t.created_at AS completed_at,
                    r.created_at AS received_at,
                    trx.urgency_level,
                    trx.due_date
                ")
                ->get();

            $onTimeCount = 0;
            $totalCompleted = $terminalLogs->count();

            foreach ($terminalLogs as $tl) {
                $dueDate = $tl->due_date
                    ? \Carbon\Carbon::parse($tl->due_date)
                    : self::calcDueDate(\Carbon\Carbon::parse($tl->received_at), $tl->urgency_level);

                if ($dueDate && \Carbon\Carbon::parse($tl->completed_at)->lte($dueDate)) {
                    $onTimeCount++;
                }
            }

            $onTimeRate = $totalCompleted > 0
                ? round(($onTimeCount / $totalCompleted) * 100, 1)
                : null;

            $rows[] = [
                'office_id'             => $oid,
                'office_name'           => $office->office_name,
                'received_count'        => $receivedCount,
                'on_time_rate'          => $onTimeRate,
                'avg_time_to_receive'   => $avgReceive?->avg_hours ? round($avgReceive->avg_hours, 1) : null,
                'avg_time_to_complete'  => $avgComplete?->avg_hours ? round($avgComplete->avg_hours, 1) : null,
                'return_rate'           => $returnRate,
                'return_reasons'        => $returnReasons,
            ];
        }

        return $rows;
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
        return [
            'Office',
            'Received',
            'On-Time Rate (%)',
            'Avg. Time to Receive (hrs)',
            'Avg. Time to Complete (hrs)',
            'Return Rate (%)',
        ];
    }

    public static function toRows(array $data): array
    {
        return array_map(fn($row) => [
            $row['office_name'],
            $row['received_count'],
            $row['on_time_rate'] ?? 'N/A',
            $row['avg_time_to_receive'] ?? 'N/A',
            $row['avg_time_to_complete'] ?? 'N/A',
            $row['return_rate'],
        ], $data);
    }
}
