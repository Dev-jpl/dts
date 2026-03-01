<?php

namespace App\Http\Controllers;

use App\Models\DocumentTransactionLog;
use Illuminate\Http\Request;

class IncomingController extends Controller
{
    private const OVERDUE_DAYS = 3;

    private const WITH = [
        'transaction',
        'transaction.document',
        'transaction.recipients',
        'transaction.attachments',
        'transaction.signatories',
    ];

    // -------------------------------------------------------------------------
    // Shared: apply search, filters, and sort to any base query
    // -------------------------------------------------------------------------
    private function applyQueryOptions($query, Request $request)
    {
        // ── Search ──────────────────────────────────────────────────────────
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('document_transaction_logs.office_name', 'ilike', "%{$search}%")
                    ->orWhere('document_transaction_logs.document_no', 'ilike', "%{$search}%")
                    ->orWhereHas('transaction', function ($tq) use ($search) {
                        $tq->where('subject',       'ilike', "%{$search}%")
                            ->orWhere('document_type', 'ilike', "%{$search}%");
                    });
            });
        }

        // ── Filter: Document Type ────────────────────────────────────────────
        if ($request->filled('document_type')) {
            $query->whereHas('transaction', function ($tq) use ($request) {
                $tq->where('document_type', $request->document_type);
            });
        }

        // ── Filter: Routing Type ─────────────────────────────────────────────
        if ($request->filled('routing')) {
            $query->whereHas('transaction', function ($tq) use ($request) {
                $tq->where('routing', $request->routing);
            });
        }

        // ── Filter: From Office ──────────────────────────────────────────────
        if ($request->filled('from_office_id')) {
            $query->where('document_transaction_logs.office_id', $request->from_office_id);
        }

        // ── Filter: Date Range ───────────────────────────────────────────────
        if ($request->filled('date_from')) {
            $query->whereDate('document_transaction_logs.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('document_transaction_logs.created_at', '<=', $request->date_to);
        }

        // ── Sort ─────────────────────────────────────────────────────────────
        $sortColumn = match ($request->query('sort_by', 'created_at')) {
            'office_name' => 'document_transaction_logs.office_name',
            default       => 'document_transaction_logs.created_at',
        };
        $sortDir = $request->query('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortColumn, $sortDir);

        return $query;
    }

    // -------------------------------------------------------------------------
    // GET /api/incoming
    // -------------------------------------------------------------------------
    public function index(Request $request)
    {
        $officeId = $request->user()->office_id;
        $perPage  = (int) $request->query('per_page', 15);

        $query = DocumentTransactionLog::with(self::WITH)->incomingForOffice($officeId);
        $data  = $this->applyQueryOptions($query, $request)->paginate($perPage);

        return response()->json(['success' => true, 'tab' => 'all', 'data' => $data]);
    }

    // -------------------------------------------------------------------------
    // GET /api/incoming/for-action
    // -------------------------------------------------------------------------
    public function forAction(Request $request)
    {
        $officeId = $request->user()->office_id;
        $perPage  = (int) $request->query('per_page', 15);

        $query = DocumentTransactionLog::with(self::WITH)->forAction($officeId);
        $data  = $this->applyQueryOptions($query, $request)->paginate($perPage);

        return response()->json(['success' => true, 'tab' => 'for_action', 'data' => $data]);
    }

    // -------------------------------------------------------------------------
    // GET /api/incoming/in-progress
    // -------------------------------------------------------------------------
    public function inProgress(Request $request)
    {
        $officeId = $request->user()->office_id;
        $perPage  = (int) $request->query('per_page', 15);

        $query = DocumentTransactionLog::with(self::WITH)->inProgress($officeId);
        $data  = $this->applyQueryOptions($query, $request)->paginate($perPage);

        return response()->json(['success' => true, 'tab' => 'in_progress', 'data' => $data]);
    }

    // -------------------------------------------------------------------------
    // GET /api/incoming/completed
    // -------------------------------------------------------------------------
    public function completed(Request $request)
    {
        $officeId = $request->user()->office_id;
        $perPage  = (int) $request->query('per_page', 15);

        $query = DocumentTransactionLog::with(self::WITH)->completedByOffice($officeId);
        $data  = $this->applyQueryOptions($query, $request)->paginate($perPage);

        return response()->json(['success' => true, 'tab' => 'completed', 'data' => $data]);
    }

    // -------------------------------------------------------------------------
    // GET /api/incoming/closed
    // -------------------------------------------------------------------------
    public function closed(Request $request)
    {
        $officeId = $request->user()->office_id;
        $perPage  = (int) $request->query('per_page', 15);

        $query = DocumentTransactionLog::with(self::WITH)->closedForOffice($officeId);
        $data  = $this->applyQueryOptions($query, $request)->paginate($perPage);

        return response()->json(['success' => true, 'tab' => 'closed', 'data' => $data]);
    }

    // -------------------------------------------------------------------------
    // GET /api/incoming/overdue
    // -------------------------------------------------------------------------
    public function overdue(Request $request)
    {
        $officeId = $request->user()->office_id;
        $perPage  = (int) $request->query('per_page', 15);
        $days     = (int) $request->query('days', self::OVERDUE_DAYS);

        $query = DocumentTransactionLog::with(self::WITH)->overdue($officeId, $days);
        $data  = $this->applyQueryOptions($query, $request)->paginate($perPage);

        return response()->json(['success' => true, 'tab' => 'overdue', 'threshold_days' => $days, 'data' => $data]);
    }

    // -------------------------------------------------------------------------
    // GET /api/incoming/counts
    // -------------------------------------------------------------------------
    public function counts(Request $request)
    {
        $officeId = $request->user()->office_id;

        return response()->json([
            'success' => true,
            'counts'  => [
                'all'         => DocumentTransactionLog::incomingForOffice($officeId)->count(),
                'for_action'  => DocumentTransactionLog::forAction($officeId)->count(),
                'overdue'     => DocumentTransactionLog::overdue($officeId, self::OVERDUE_DAYS)->count(),
                'in_progress' => DocumentTransactionLog::inProgress($officeId)->count(),
                'completed'   => DocumentTransactionLog::completedByOffice($officeId)->count(),
                'closed'      => DocumentTransactionLog::closedForOffice($officeId)->count(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/incoming/filters — dynamic dropdown options
    // FIX: qualify all ambiguous column references with table prefix
    // -------------------------------------------------------------------------
    public function filters(Request $request)
    {
        $officeId = $request->user()->office_id;

        // Get base transaction_nos scoped to this office
        $baseIds = DocumentTransactionLog::incomingForOffice($officeId)
            ->pluck('document_transaction_logs.transaction_no'); // ← qualified

        // Distinct document types
        $documentTypes = DocumentTransactionLog::whereIn('document_transaction_logs.transaction_no', $baseIds)
            ->join('document_transactions as dt', 'dt.transaction_no', '=', 'document_transaction_logs.transaction_no')
            ->select('dt.document_type')
            ->distinct()
            ->orderBy('dt.document_type')
            ->pluck('dt.document_type')
            ->filter()
            ->values();

        // Distinct routing types
        $routingTypes = DocumentTransactionLog::whereIn('document_transaction_logs.transaction_no', $baseIds)
            ->join('document_transactions as dt', 'dt.transaction_no', '=', 'document_transaction_logs.transaction_no')
            ->select('dt.routing')
            ->distinct()
            ->pluck('dt.routing')
            ->filter()
            ->values();

        // Distinct sender offices
        $senderOffices = DocumentTransactionLog::whereIn('document_transaction_logs.transaction_no', $baseIds)
            ->select('document_transaction_logs.office_id', 'document_transaction_logs.office_name')
            ->distinct()
            ->get()
            ->unique('office_id')
            ->values();

        return response()->json([
            'success'        => true,
            'document_types' => $documentTypes,
            'routing_types'  => $routingTypes,
            'sender_offices' => $senderOffices,
        ]);
    }
}
