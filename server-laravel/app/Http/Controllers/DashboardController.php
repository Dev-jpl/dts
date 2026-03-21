<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentTransaction;
use App\Models\DocumentTransactionLog;
use App\Services\OverdueService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const WITH_TRANSACTION = [
        'transaction',
        'transaction.document',
        'transaction.recipients',
        'transaction.logs',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/dashboard/for-action
    // Documents released TO this office with no terminal action yet,
    // sorted by urgency (Urgent first) then released_at ASC.
    // ─────────────────────────────────────────────────────────────────────────
    public function forAction(Request $request)
    {
        $officeId = $request->user()->office_id;
        $perPage  = (int) $request->query('per_page', 10);

        $logs = DocumentTransactionLog::with(self::WITH_TRANSACTION)
            ->forAction($officeId)
            ->join('document_transactions as dt_fa', 'dt_fa.transaction_no', '=', 'document_transaction_logs.transaction_no')
            ->select('document_transaction_logs.*', 'dt_fa.urgency_level as urgency_level')
            ->orderByRaw("
                CASE dt_fa.urgency_level
                    WHEN 'Urgent'  THEN 1
                    WHEN 'High'    THEN 2
                    WHEN 'Normal'  THEN 3
                    WHEN 'Routine' THEN 4
                    ELSE 5
                END ASC
            ")
            ->orderBy('document_transaction_logs.created_at', 'asc')
            ->paginate($perPage);

        // Attach overdue info to each item
        $logs->getCollection()->transform(function ($log) use ($officeId) {
            if ($log->transaction) {
                $log->transaction->setRelation('recipients', $log->transaction->recipients);
                $log->transaction->setRelation('logs', $log->transaction->logs);
                $log->days_overdue = OverdueService::daysUntilDue($log->transaction, $officeId);
                $log->is_overdue   = OverdueService::isOverdueForOffice($log->transaction, $officeId);
            }
            return $log;
        });

        return response()->json(['success' => true, 'data' => $logs]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/dashboard/overdue
    // FA items this office has received but not actioned, past due date.
    // Sorted most overdue first.
    // ─────────────────────────────────────────────────────────────────────────
    public function overdue(Request $request)
    {
        $officeId = $request->user()->office_id;
        $perPage  = (int) $request->query('per_page', 10);

        // Get all Received logs for this office that are still pending terminal action
        $logs = DocumentTransactionLog::with(self::WITH_TRANSACTION)
            ->inProgress($officeId)
            ->paginate(100); // load more to filter in PHP (OverdueService needs related data)

        // Filter to truly overdue items via OverdueService
        $overdueItems = $logs->getCollection()->filter(function ($log) use ($officeId) {
            if (!$log->transaction) return false;
            return OverdueService::isOverdueForOffice($log->transaction, $officeId);
        })->map(function ($log) use ($officeId) {
            $log->days_overdue = abs(OverdueService::daysUntilDue($log->transaction, $officeId) ?? 0);
            $log->due_date     = OverdueService::getDueDate($log->transaction, $officeId)?->toDateString();
            return $log;
        })->sortByDesc('days_overdue')->values();

        // Manual pagination
        $page    = (int) $request->query('page', 1);
        $total   = $overdueItems->count();
        $items   = $overdueItems->forPage($page, $perPage)->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'data'          => $items,
                'total'         => $total,
                'per_page'      => $perPage,
                'current_page'  => $page,
                'last_page'     => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/dashboard/drafts
    // Draft transactions created by this office (not yet released).
    // ─────────────────────────────────────────────────────────────────────────
    public function drafts(Request $request)
    {
        $officeId = $request->user()->office_id;
        $perPage  = (int) $request->query('per_page', 10);

        $data = DocumentTransaction::with(['document'])
            ->where('office_id', $officeId)
            ->where('status', 'Draft')
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/dashboard/outgoing?status=Active|Returned|Completed
    // Documents originated by this office, grouped by status.
    // ─────────────────────────────────────────────────────────────────────────
    public function outgoing(Request $request)
    {
        $officeId = $request->user()->office_id;
        $perPage  = (int) $request->query('per_page', 10);
        $status   = $request->query('status', null);

        $allowedStatuses = ['Active', 'Returned', 'Completed'];

        $query = Document::with([
            'transactions' => fn($q) => $q->with([
                'recipients' => fn($r) => $r->select('id', 'transaction_no', 'document_no', 'office_id', 'office_name', 'recipient_type', 'sequence', 'isActive'),
                'logs' => fn($l) => $l->select('id', 'transaction_no', 'status', 'office_id', 'office_name', 'created_at')
                    ->whereIn('status', ['Released', 'Received', 'Done', 'Forwarded', 'Returned To Sender'])
                    ->orderBy('created_at', 'desc')
            ])->select('transaction_no', 'document_no', 'transaction_type', 'routing', 'action_type', 'status', 'urgency_level', 'due_date', 'created_at', 'updated_at')
        ])
            ->where('office_id', $officeId)
            ->whereIn('status', $status && in_array($status, $allowedStatuses)
                ? [$status]
                : $allowedStatuses
            )
            ->orderBy('updated_at', 'desc');

        $data = $query->paginate($perPage);

        // Transform to include progress info per transaction
        $data->getCollection()->transform(function ($doc) {
            $doc->transactions->transform(function ($trx) {
                $defaultRecipients = $trx->recipients->where('recipient_type', 'default');
                $totalRecipients = $defaultRecipients->count();
                
                // Count recipients who have received (have a Received log)
                $receivedOffices = $trx->logs->where('status', 'Received')->pluck('office_id')->unique();
                $received = $defaultRecipients->filter(fn($r) => $receivedOffices->contains($r->office_id))->count();
                
                // Count recipients who completed (have Done/Forwarded/Returned log)
                $completedOffices = $trx->logs->whereIn('status', ['Done', 'Forwarded', 'Returned To Sender'])->pluck('office_id')->unique();
                $completed = $defaultRecipients->filter(fn($r) => $completedOffices->contains($r->office_id))->count();
                
                $trx->progress = [
                    'total_recipients' => $totalRecipients,
                    'received' => $received,
                    'completed' => $completed,
                    'pending' => $totalRecipients - $completed,
                ];
                
                // Get recipient names for display (as array)
                $trx->recipient_names = $defaultRecipients->pluck('office_name')->values()->toArray();
                
                // Get latest activity
                $latestLog = $trx->logs->first();
                $trx->latest_activity = $latestLog ? [
                    'status' => $latestLog->status,
                    'office_name' => $latestLog->office_name,
                    'created_at' => $latestLog->created_at,
                ] : null;
                
                return $trx;
            });
            return $doc;
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/dashboard/stats?period=week|month|quarter|year
    // Summary counts for the office, with delta vs previous period.
    // ─────────────────────────────────────────────────────────────────────────
    public function stats(Request $request)
    {
        $officeId = $request->user()->office_id;
        $period   = $request->query('period', 'month');

        [$currentStart, $currentEnd, $prevStart, $prevEnd] = $this->getPeriodBounds($period);

        $current  = $this->computeStats($officeId, $currentStart, $currentEnd);
        $previous = $this->computeStats($officeId, $prevStart, $prevEnd);

        $delta = [];
        foreach ($current as $key => $value) {
            $prev          = $previous[$key] ?? 0;
            $delta[$key]   = $prev > 0
                ? round((($value - $prev) / $prev) * 100, 1)
                : ($value > 0 ? 100 : 0);
        }

        return response()->json([
            'success'  => true,
            'period'   => $period,
            'current'  => $current,
            'previous' => $previous,
            'delta'    => $delta,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/dashboard/activity
    // Last 20 transaction log entries that involve this office.
    // ─────────────────────────────────────────────────────────────────────────
    public function activity(Request $request)
    {
        $officeId = $request->user()->office_id;

        $logs = DocumentTransactionLog::with(['transaction.document'])
            ->where(function ($q) use ($officeId) {
                $q->where('office_id', $officeId)
                  ->orWhere('routed_office_id', $officeId);
            })
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($log) {
                return [
                    'id'             => $log->id,
                    'status'         => $log->status,
                    'office_name'    => $log->office_name,
                    'document_no'    => $log->document_no,
                    'transaction_no' => $log->transaction_no,
                    'subject'        => $log->transaction?->subject,
                    'document_type'  => $log->transaction?->document_type,
                    'created_at'     => $log->created_at,
                ];
            });

        return response()->json(['success' => true, 'data' => $logs]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/dashboard/team   [Superior + Admin only]
    // Performance summary per subordinate office.
    // ─────────────────────────────────────────────────────────────────────────
    public function team(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['superior', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Not authorized.'], 403);
        }

        // Get subordinate offices (offices whose parent_office_id matches the user's office)
        $subordinateOffices = DB::table('office_libraries')
            ->where('parent_office_id', $user->office_id)
            ->select('id', 'office_name')
            ->get();

        $teamData = $subordinateOffices->map(function ($office) {
            $officeId = $office->id;

            $totalReceived = DocumentTransactionLog::where('office_id', $officeId)
                ->where('status', 'Received')
                ->count();

            $overdueCount = OverdueService::countOverdueForOffice($officeId);

            // Avg turnaround: from Received to terminal action in days
            $avgTurnaround = DB::table('document_transaction_logs as r')
                ->join('document_transaction_logs as t', function ($join) use ($officeId) {
                    $join->on('t.transaction_no', '=', 'r.transaction_no')
                         ->where('t.office_id', $officeId)
                         ->whereIn('t.status', ['Done', 'Forwarded', 'Returned To Sender']);
                })
                ->where('r.office_id', $officeId)
                ->where('r.status', 'Received')
                ->selectRaw("AVG(EXTRACT(EPOCH FROM (t.created_at - r.created_at)) / 86400) as avg_days")
                ->value('avg_days');

            // On-time rate: completed within due date
            $completedLogs = DocumentTransactionLog::where('office_id', $officeId)
                ->whereIn('status', ['Done', 'Forwarded', 'Returned To Sender'])
                ->with(['transaction', 'transaction.recipients', 'transaction.logs'])
                ->get();

            $onTimeCount = 0;
            $totalCompleted = $completedLogs->count();

            foreach ($completedLogs as $log) {
                if ($log->transaction) {
                    $daysUntilDue = OverdueService::daysUntilDue($log->transaction, $officeId);
                    // If daysUntilDue is non-negative at time of completion, it was on time
                    // We approximate: if not overdue, count as on-time
                    if ($daysUntilDue !== null && $daysUntilDue >= 0) {
                        $onTimeCount++;
                    }
                }
            }

            $onTimeRate = $totalCompleted > 0
                ? round(($onTimeCount / $totalCompleted) * 100, 1)
                : null;

            return [
                'office_id'         => $officeId,
                'office_name'       => $office->office_name,
                'total_received'    => $totalReceived,
                'overdue_count'     => $overdueCount,
                'avg_turnaround'    => $avgTurnaround ? round($avgTurnaround, 1) : null,
                'on_time_rate'      => $onTimeRate,
            ];
        });

        return response()->json(['success' => true, 'data' => $teamData]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/dashboard/system   [Admin only]
    // System-wide health metrics.
    // ─────────────────────────────────────────────────────────────────────────
    public function system(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Not authorized.'], 403);
        }

        $totalProcessing = DocumentTransaction::where('status', 'Processing')->count();
        $totalDocumentsToday = Document::whereDate('created_at', today())->count();

        // 30-day completion rate
        $since30 = now()->subDays(30);
        $completed30 = DocumentTransaction::where('status', 'Completed')
            ->where('updated_at', '>=', $since30)
            ->count();
        $processing30 = DocumentTransaction::where('status', 'Processing')
            ->where('created_at', '>=', $since30)
            ->count();
        $completionRate30d = ($completed30 + $processing30) > 0
            ? round(($completed30 / ($completed30 + $processing30)) * 100, 1)
            : 0;

        // Total overdue across all offices
        $allOffices = DB::table('office_libraries')->pluck('id');
        $totalOverdue = 0;

        // Top 5 overdue offices
        $officeOverdueCounts = [];
        foreach ($allOffices as $officeId) {
            $count = OverdueService::countOverdueForOffice($officeId);
            $totalOverdue += $count;
            if ($count > 0) {
                $officeOverdueCounts[$officeId] = $count;
            }
        }

        arsort($officeOverdueCounts);
        $top5OfficeIds = array_slice(array_keys($officeOverdueCounts), 0, 5);

        $topOverdueOffices = DB::table('office_libraries')
            ->whereIn('id', $top5OfficeIds)
            ->select('id', 'office_name')
            ->get()
            ->map(fn($o) => [
                'office_id'     => $o->id,
                'office_name'   => $o->office_name,
                'overdue_count' => $officeOverdueCounts[$o->id] ?? 0,
            ])
            ->sortByDesc('overdue_count')
            ->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_processing'       => $totalProcessing,
                'total_overdue'          => $totalOverdue,
                'total_documents_today'  => $totalDocumentsToday,
                'completion_rate_30d'    => $completionRate30d,
                'top_overdue_offices'    => $topOverdueOffices,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function getPeriodBounds(string $period): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'week':
                $currentStart = $now->copy()->startOfWeek();
                $currentEnd   = $now->copy()->endOfWeek();
                $prevStart    = $now->copy()->subWeek()->startOfWeek();
                $prevEnd      = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'quarter':
                $currentStart = $now->copy()->startOfQuarter();
                $currentEnd   = $now->copy()->endOfQuarter();
                $prevStart    = $now->copy()->subQuarter()->startOfQuarter();
                $prevEnd      = $now->copy()->subQuarter()->endOfQuarter();
                break;
            case 'year':
                $currentStart = $now->copy()->startOfYear();
                $currentEnd   = $now->copy()->endOfYear();
                $prevStart    = $now->copy()->subYear()->startOfYear();
                $prevEnd      = $now->copy()->subYear()->endOfYear();
                break;
            default: // month
                $currentStart = $now->copy()->startOfMonth();
                $currentEnd   = $now->copy()->endOfMonth();
                $prevStart    = $now->copy()->subMonth()->startOfMonth();
                $prevEnd      = $now->copy()->subMonth()->endOfMonth();
        }

        return [$currentStart, $currentEnd, $prevStart, $prevEnd];
    }

    private function computeStats(string $officeId, Carbon $start, Carbon $end): array
    {
        // Sent: Released logs from this office in period
        $sent = DocumentTransactionLog::where('office_id', $officeId)
            ->where('status', 'Released')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // Received: Received logs for this office in period
        $received = DocumentTransactionLog::where('office_id', $officeId)
            ->where('status', 'Received')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // For Action: released to this office in the period with no terminal action
        $forAction = DocumentTransactionLog::forAction($officeId)
            ->whereBetween('document_transaction_logs.created_at', [$start, $end])
            ->count();

        // Completed: terminal actions taken by this office in period
        $completed = DocumentTransactionLog::where('office_id', $officeId)
            ->whereIn('status', ['Done', 'Forwarded', 'Returned To Sender'])
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // Returned: return-to-sender actions taken in period
        $returned = DocumentTransactionLog::where('office_id', $officeId)
            ->where('status', 'Returned To Sender')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // Overdue: current overdue count (not period-bound)
        $overdue = OverdueService::countOverdueForOffice($officeId);

        return [
            'sent'       => $sent,
            'received'   => $received,
            'for_action' => $forAction,
            'completed'  => $completed,
            'returned'   => $returned,
            'overdue'    => $overdue,
        ];
    }
}
