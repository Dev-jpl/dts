<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\UserSavedSearch;
use App\Models\DocumentRecipient;
use App\Models\DocumentTransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Full search with filters
     * GET /api/search
     */
    public function search(Request $request)
    {
        $user = $request->user();
        $officeId = $user->office_id;

        $query = Document::query()
            ->with([
                'transactions' => fn($q) => $q->orderBy('created_at', 'desc'),
                'transactions.recipients',
                'attachments',
                'notes',
            ]);

        // Full-text search on subject
        if ($request->filled('q')) {
            $searchTerm = $request->input('q');
            $query->where(function ($q) use ($searchTerm) {
                // Full-text search using PostgreSQL
                $q->whereRaw(
                    "to_tsvector('english', subject) @@ plainto_tsquery('english', ?)",
                    [$searchTerm]
                )
                ->orWhere('document_no', 'ILIKE', "%{$searchTerm}%")
                ->orWhere('subject', 'ILIKE', "%{$searchTerm}%");
            });
        }

        // Filter: document_no
        if ($request->filled('document_no')) {
            $query->where('document_no', 'ILIKE', "%{$request->input('document_no')}%");
        }

        // Filter: document_type
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->input('document_type'));
        }

        // Filter: action_type
        if ($request->filled('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        // Filter: origin_type
        if ($request->filled('origin_type')) {
            $query->where('origin_type', $request->input('origin_type'));
        }

        // Filter: sender (name)
        if ($request->filled('sender')) {
            $query->where('sender', 'ILIKE', "%{$request->input('sender')}%");
        }

        // Filter: sender_office
        if ($request->filled('sender_office')) {
            $query->where('sender_office', 'ILIKE', "%{$request->input('sender_office')}%");
        }

        // Filter: sender_email
        if ($request->filled('sender_email')) {
            $query->where('sender_email', 'ILIKE', "%{$request->input('sender_email')}%");
        }

        // Filter: origin_office
        if ($request->filled('origin_office')) {
            $query->where('office_id', $request->input('origin_office'));
        }

        // Filter: status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter: recipient_office
        if ($request->filled('recipient_office')) {
            $query->whereHas('recipients', function ($q) use ($request) {
                $q->where('office_id', $request->input('recipient_office'));
            });
        }

        // Filter: routing_type (column is called 'routing' in DB)
        if ($request->filled('routing_type')) {
            $query->whereHas('transactions', function ($q) use ($request) {
                $q->where('routing', $request->input('routing_type'));
            });
        }

        // Filter: urgency_level
        if ($request->filled('urgency_level')) {
            $query->whereHas('transactions', function ($q) use ($request) {
                $q->where('urgency_level', $request->input('urgency_level'));
            });
        }

        // Filter: overdue_only
        if ($request->boolean('overdue_only')) {
            $query->whereHas('transactions.recipients', function ($q) {
                $q->where('isActive', true)
                  ->whereNotNull('due_date')
                  ->where('due_date', '<', now());
            });
        }

        // Filter: has_attachments
        if ($request->boolean('has_attachments')) {
            $query->has('attachments');
        }

        // Filter: has_notes
        if ($request->boolean('has_notes')) {
            $query->has('notes');
        }

        // Filter: has_returned (documents that have been returned at some point)
        if ($request->boolean('has_returned')) {
            $query->whereHas('logs', function ($q) {
                $q->where('status', 'Returned To Sender');
            });
        }

        // Filter: return_reason
        if ($request->filled('return_reason')) {
            $query->whereHas('logs', function ($q) use ($request) {
                $q->where('status', 'Returned To Sender')
                  ->where('reason', 'ILIKE', "%{$request->input('return_reason')}%");
            });
        }

        // Date range filters
        if ($request->filled('released_from')) {
            $query->whereHas('logs', function ($q) use ($request) {
                $q->where('status', 'Released')
                  ->whereDate('logged_at', '>=', $request->input('released_from'));
            });
        }
        if ($request->filled('released_to')) {
            $query->whereHas('logs', function ($q) use ($request) {
                $q->where('status', 'Released')
                  ->whereDate('logged_at', '<=', $request->input('released_to'));
            });
        }

        if ($request->filled('received_from')) {
            $query->whereHas('logs', function ($q) use ($request) {
                $q->where('status', 'Received')
                  ->whereDate('logged_at', '>=', $request->input('received_from'));
            });
        }
        if ($request->filled('received_to')) {
            $query->whereHas('logs', function ($q) use ($request) {
                $q->where('status', 'Received')
                  ->whereDate('logged_at', '<=', $request->input('received_to'));
            });
        }

        // Filter: days_since_released
        if ($request->filled('days_since_released')) {
            $days = (int) $request->input('days_since_released');
            $query->whereHas('logs', function ($q) use ($days) {
                $q->where('status', 'Released')
                  ->where('logged_at', '<=', now()->subDays($days));
            });
        }

        // Filter: days_overdue
        if ($request->filled('days_overdue')) {
            $days = (int) $request->input('days_overdue');
            $query->whereHas('transactions.recipients', function ($q) use ($days) {
                $q->where('isActive', true)
                  ->whereNotNull('due_date')
                  ->where('due_date', '<=', now()->subDays($days));
            });
        }

        // Access control: Users can only see documents where they are:
        // - Origin office
        // - Active recipient (excluding BCC for non-origin)
        $query->where(function ($q) use ($officeId) {
            $q->where('office_id', $officeId)
              ->orWhereHas('recipients', function ($r) use ($officeId) {
                  $r->where('office_id', $officeId)
                    ->where('recipient_type', '!=', 'bcc');
              });
        });

        // Sorting
        $sortBy = $request->input('sort_by', 'relevance');
        switch ($sortBy) {
            case 'date':
                $query->orderBy('created_at', 'desc');
                break;
            case 'status':
                $query->orderBy('status');
                break;
            case 'urgency':
                // Join with transactions to sort by urgency
                $query->orderByRaw("(
                    SELECT CASE urgency_level
                        WHEN 'Urgent' THEN 1
                        WHEN 'High' THEN 2
                        WHEN 'Normal' THEN 3
                        WHEN 'Routine' THEN 4
                        ELSE 5
                    END
                    FROM document_transactions
                    WHERE document_transactions.document_no = documents.document_no
                    ORDER BY created_at DESC
                    LIMIT 1
                )");
                break;
            case 'overdue':
                $query->orderByRaw("(
                    SELECT due_date
                    FROM document_recipients
                    WHERE document_recipients.document_no = documents.document_no
                    AND \"isActive\" = true
                    AND due_date IS NOT NULL
                    ORDER BY due_date ASC
                    LIMIT 1
                ) ASC NULLS LAST");
                break;
            default: // relevance - newest first for now
                $query->orderBy('created_at', 'desc');
        }

        // Paginate
        $perPage = min($request->input('per_page', 20), 100);
        $results = $query->paginate($perPage);

        // Transform results to hide BCC from non-origin callers
        $results->getCollection()->transform(function ($document) use ($officeId) {
            if ($document->office_id !== $officeId) {
                // Hide BCC recipients from non-origin viewers
                $document->recipients = $document->recipients->filter(
                    fn($r) => $r->recipient_type !== 'bcc'
                );
                foreach ($document->transactions as $trx) {
                    $trx->recipients = $trx->recipients->filter(
                        fn($r) => $r->recipient_type !== 'bcc'
                    );
                }
            }
            return $document;
        });

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Quick search for nav header dropdown (top 5)
     * GET /api/search/quick
     */
    public function quick(Request $request)
    {
        $user = $request->user();
        $officeId = $user->office_id;
        $searchTerm = $request->input('q', '');

        if (strlen($searchTerm) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $results = Document::query()
            ->select('document_no', 'subject', 'status', 'document_type', 'created_at')
            ->where(function ($q) use ($searchTerm) {
                $q->where('document_no', 'ILIKE', "%{$searchTerm}%")
                  ->orWhere('subject', 'ILIKE', "%{$searchTerm}%");
            })
            ->where(function ($q) use ($officeId) {
                $q->where('office_id', $officeId)
                  ->orWhereHas('recipients', function ($r) use ($officeId) {
                      $r->where('office_id', $officeId)
                        ->where('recipient_type', '!=', 'bcc');
                  });
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Get user's saved searches
     * GET /api/search/saved
     */
    public function savedIndex(Request $request)
    {
        $searches = UserSavedSearch::where('user_id', $request->user()->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $searches,
        ]);
    }

    /**
     * Create a saved search
     * POST /api/search/saved
     */
    public function savedStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'filters_json' => 'required|array',
            'sort_by' => 'nullable|string|max:50',
        ]);

        $search = UserSavedSearch::create([
            'user_id' => $request->user()->id,
            'name' => $request->input('name'),
            'filters_json' => $request->input('filters_json'),
            'sort_by' => $request->input('sort_by', 'relevance'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Search saved successfully.',
            'data' => $search,
        ], 201);
    }

    /**
     * Delete a saved search
     * DELETE /api/search/saved/{id}
     */
    public function savedDestroy(Request $request, $id)
    {
        $search = UserSavedSearch::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$search) {
            return response()->json([
                'success' => false,
                'message' => 'Saved search not found.',
            ], 404);
        }

        $search->delete();

        return response()->json([
            'success' => true,
            'message' => 'Saved search deleted.',
        ]);
    }

    /**
     * Get available filter options
     * GET /api/search/filters
     * 
     * Only returns filter options from documents where the user's office is involved
     * (as origin or as recipient - default, cc, bcc)
     */
    public function filters(Request $request)
    {
        $user = $request->user();
        $officeId = $user->office_id;

        // Get document_nos where user's office is involved
        $involvedDocumentNos = Document::where('office_id', $officeId)
            ->orWhereHas('recipients', function ($q) use ($officeId) {
                $q->where('office_id', $officeId);
            })
            ->pluck('document_no');

        // Get distinct document types from involved documents
        $documentTypes = DB::table('documents')
            ->whereIn('document_no', $involvedDocumentNos)
            ->distinct()
            ->whereNotNull('document_type')
            ->orderBy('document_type')
            ->pluck('document_type');

        // Get distinct action types from involved documents
        $actionTypes = DB::table('documents')
            ->whereIn('document_no', $involvedDocumentNos)
            ->distinct()
            ->whereNotNull('action_type')
            ->orderBy('action_type')
            ->pluck('action_type');

        // Get distinct origin types from involved documents
        $originTypes = DB::table('documents')
            ->whereIn('document_no', $involvedDocumentNos)
            ->distinct()
            ->whereNotNull('origin_type')
            ->orderBy('origin_type')
            ->pluck('origin_type');

        // Get offices that appear in involved documents (origin or recipient)
        $originOfficeIds = DB::table('documents')
            ->whereIn('document_no', $involvedDocumentNos)
            ->distinct()
            ->pluck('office_id');

        $recipientOfficeIds = DB::table('document_recipients')
            ->join('document_transactions', 'document_recipients.transaction_no', '=', 'document_transactions.transaction_no')
            ->whereIn('document_transactions.document_no', $involvedDocumentNos)
            ->distinct()
            ->pluck('document_recipients.office_id');

        $allOfficeIds = $originOfficeIds->merge($recipientOfficeIds)->unique();

        $offices = DB::table('office_libraries')
            ->whereIn('id', $allOfficeIds)
            ->select('id as office_id', 'office_name')
            ->orderBy('office_name')
            ->get();

        // Static values
        $statuses = ['Draft', 'Active', 'Returned', 'Completed', 'Closed'];
        $routingTypes = ['Single', 'Multiple', 'Sequential'];
        $urgencyLevels = ['Urgent', 'High', 'Normal', 'Routine'];

        return response()->json([
            'success' => true,
            'data' => [
                'document_types' => $documentTypes,
                'action_types' => $actionTypes,
                'origin_types' => $originTypes,
                'offices' => $offices,
                'statuses' => $statuses,
                'routing_types' => $routingTypes,
                'urgency_levels' => $urgencyLevels,
            ],
        ]);
    }
}
