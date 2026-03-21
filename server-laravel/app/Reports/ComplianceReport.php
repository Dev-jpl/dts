<?php

namespace App\Reports;

use Illuminate\Support\Facades\DB;

/**
 * ISO Compliance Report.
 *
 * Maps DTS metrics to ISO 9001:2015 and ISO 15489-1:2016 clauses.
 *
 * ISO 9001: 9.1.1  Monitoring & Measurement
 *           9.1.3  Analysis & Evaluation
 *           8.7    Nonconforming Outputs
 *           10.2   Corrective Action
 *
 * ISO 15489: 8.3   Creation of Records
 *            8.4   Use of Records
 *            9.8   Disposal
 */
class ComplianceReport
{
    public static function getData(?array $officeIds, string $periodStart, string $periodEnd): array
    {
        // ── Base scoped query helper ───────────────────────────────────────────
        $scope = function ($table, $column = 'office_id') use ($officeIds) {
            $q = DB::table($table);
            if ($officeIds !== null) {
                $q->whereIn($column, $officeIds);
            }
            return $q;
        };

        // ─────────────────────────────────────────────────────────────────────
        // ISO 9001 — Clause 9.1.1: Monitoring & Measurement
        // Track receipt rates, completion rates, return rates across the period.
        // ─────────────────────────────────────────────────────────────────────
        $receivedInPeriod = $scope('document_transaction_logs')
            ->where('status', 'Received')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $completedInPeriod = $scope('document_transaction_logs')
            ->whereIn('status', ['Done', 'Forwarded', 'Returned To Sender'])
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $returnedInPeriod = $scope('document_transaction_logs')
            ->where('status', 'Returned To Sender')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $totalActiveTransactions = $scope('document_transactions')
            ->where('status', 'Processing')
            ->count();

        $processingRate = ($receivedInPeriod + $completedInPeriod) > 0
            ? round(($completedInPeriod / ($receivedInPeriod + $completedInPeriod)) * 100, 1)
            : 0;

        $nine_one_one = [
            'label'       => 'Monitoring & Measurement',
            'description' => 'Documents received, processed, and completed within the period.',
            'metrics'     => [
                'documents_received'        => $receivedInPeriod,
                'actions_completed'         => $completedInPeriod,
                'active_transactions'       => $totalActiveTransactions,
                'processing_rate_pct'       => $processingRate,
            ],
        ];

        // ─────────────────────────────────────────────────────────────────────
        // ISO 9001 — Clause 9.1.3: Analysis & Evaluation
        // Trend analysis: breakdown by document type and urgency.
        // ─────────────────────────────────────────────────────────────────────
        $byDocType = $scope('document_transaction_logs', 'document_transaction_logs.office_id')
            ->join('document_transactions as trx', 'trx.transaction_no', '=', 'document_transaction_logs.transaction_no')
            ->where('document_transaction_logs.status', 'Received')
            ->whereBetween('document_transaction_logs.created_at', [$periodStart, $periodEnd])
            ->selectRaw('trx.document_type, COUNT(*) as count')
            ->groupBy('trx.document_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn($r) => ['document_type' => $r->document_type, 'count' => $r->count])
            ->toArray();

        $byUrgency = $scope('document_transaction_logs', 'document_transaction_logs.office_id')
            ->join('document_transactions as trx', 'trx.transaction_no', '=', 'document_transaction_logs.transaction_no')
            ->where('document_transaction_logs.status', 'Received')
            ->whereBetween('document_transaction_logs.created_at', [$periodStart, $periodEnd])
            ->selectRaw('trx.urgency_level, COUNT(*) as count')
            ->groupBy('trx.urgency_level')
            ->orderByDesc('count')
            ->get()
            ->map(fn($r) => ['urgency_level' => $r->urgency_level, 'count' => $r->count])
            ->toArray();

        $nine_one_three = [
            'label'       => 'Analysis & Evaluation',
            'description' => 'Breakdown of document flow by type and urgency level.',
            'metrics'     => [
                'by_document_type' => $byDocType,
                'by_urgency_level' => $byUrgency,
            ],
        ];

        // ─────────────────────────────────────────────────────────────────────
        // ISO 9001 — Clause 8.7: Nonconforming Outputs
        // Documents returned to sender (routing failures).
        // ─────────────────────────────────────────────────────────────────────
        $returnedTransactions = $scope('document_transactions')
            ->where('status', 'Returned')
            ->count();

        $returnReasons = $scope('document_transaction_logs')
            ->where('status', 'Returned To Sender')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->whereNotNull('reason')
            ->selectRaw('reason, COUNT(*) as count')
            ->groupBy('reason')
            ->orderByDesc('count')
            ->get()
            ->map(fn($r) => ['reason' => $r->reason, 'count' => $r->count])
            ->toArray();

        $returnRate = $receivedInPeriod > 0
            ? round(($returnedInPeriod / $receivedInPeriod) * 100, 1)
            : 0;

        $eight_seven = [
            'label'       => 'Nonconforming Outputs',
            'description' => 'Documents returned to sender (nonconforming routing).',
            'metrics'     => [
                'returned_in_period'     => $returnedInPeriod,
                'total_returned_active'  => $returnedTransactions,
                'return_rate_pct'        => $returnRate,
                'return_reasons'         => $returnReasons,
            ],
        ];

        // ─────────────────────────────────────────────────────────────────────
        // ISO 9001 — Clause 10.2: Corrective Action
        // Return reason analysis and repeat return patterns.
        // ─────────────────────────────────────────────────────────────────────
        $repeatOffenders = DB::table('document_transaction_logs')
            ->where('status', 'Returned To Sender')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->when($officeIds !== null, fn($q) => $q->whereIn('office_id', $officeIds))
            ->selectRaw('office_name, COUNT(*) as return_count')
            ->groupBy('office_name')
            ->havingRaw('COUNT(*) > 1')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['office_name' => $r->office_name, 'return_count' => $r->return_count])
            ->toArray();

        $ten_two = [
            'label'       => 'Corrective Action',
            'description' => 'Offices with repeated return-to-sender actions (requiring corrective review).',
            'metrics'     => [
                'repeat_return_offices' => $repeatOffenders,
                'total_return_reasons'  => count($returnReasons),
            ],
        ];

        // ─────────────────────────────────────────────────────────────────────
        // ISO 15489 — Clause 8.3: Creation of Records
        // New documents profiled and released in the period.
        // ─────────────────────────────────────────────────────────────────────
        $docsCreated = DB::table('documents')
            ->when($officeIds !== null, fn($q) => $q->whereIn('office_id', $officeIds))
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $docsReleased = DB::table('document_transaction_logs')
            ->when($officeIds !== null, fn($q) => $q->whereIn('office_id', $officeIds))
            ->where('status', 'Released')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $eight_three = [
            'label'       => 'Creation of Records',
            'description' => 'Documents created and released into the tracking system.',
            'metrics'     => [
                'documents_created'  => $docsCreated,
                'documents_released' => $docsReleased,
            ],
        ];

        // ─────────────────────────────────────────────────────────────────────
        // ISO 15489 — Clause 8.4: Use of Records
        // Active participants, actions taken, notes added.
        // ─────────────────────────────────────────────────────────────────────
        $actionsLogged = DB::table('document_transaction_logs')
            ->when($officeIds !== null, fn($q) => $q->whereIn('office_id', $officeIds))
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $notesAdded = DB::table('document_notes')
            ->when($officeIds !== null, fn($q) => $q->whereIn('office_id', $officeIds))
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $eight_four = [
            'label'       => 'Use of Records',
            'description' => 'Total actions logged and official notes added — indicating active record use.',
            'metrics'     => [
                'actions_logged' => $actionsLogged,
                'notes_added'    => $notesAdded,
            ],
        ];

        // ─────────────────────────────────────────────────────────────────────
        // ISO 15489 — Clause 9.8: Disposal
        // Documents closed (archived) in the period.
        // ─────────────────────────────────────────────────────────────────────
        $docsClosed = DB::table('document_transaction_logs')
            ->when($officeIds !== null, fn($q) => $q->whereIn('office_id', $officeIds))
            ->where('status', 'Closed')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $nine_eight = [
            'label'       => 'Disposal',
            'description' => 'Documents formally closed (records disposed per retention policy).',
            'metrics'     => [
                'documents_closed' => $docsClosed,
            ],
        ];

        return [
            'iso_9001' => [
                '9.1.1' => $nine_one_one,
                '9.1.3' => $nine_one_three,
                '8.7'   => $eight_seven,
                '10.2'  => $ten_two,
            ],
            'iso_15489' => [
                '8.3' => $eight_three,
                '8.4' => $eight_four,
                '9.8' => $nine_eight,
            ],
        ];
    }

    public static function flatRows(array $data): array
    {
        $rows = [['Clause', 'Standard', 'Metric', 'Value']];

        $standards = [
            'iso_9001'  => 'ISO 9001:2015',
            'iso_15489' => 'ISO 15489-1:2016',
        ];

        foreach ($standards as $key => $label) {
            foreach ($data[$key] as $clause => $section) {
                foreach ($section['metrics'] as $metric => $value) {
                    if (is_array($value)) continue; // skip sub-arrays
                    $rows[] = [
                        $clause,
                        $label,
                        str_replace('_', ' ', ucwords($metric, '_')),
                        $value,
                    ];
                }
            }
        }

        return $rows;
    }
}
