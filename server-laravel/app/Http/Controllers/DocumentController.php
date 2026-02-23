<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentRecipient;
use App\Models\DocumentTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * GET /api/documents
     *
     * Returns all documents created by the authenticated user's OFFICE,
     * ordered by created_at descending.
     *
     * Query params (all optional):
     *   ?status=Draft|Processing|Archived
     *   ?search=keyword               → subject, document_no, document_type, action_type
     *   ?document_type=Memorandum
     *   ?recipient_office_id=abc-123  → only docs that have this office as a recipient
     *   ?date_from=2026-01-01
     *   ?date_to=2026-12-31
     *   ?per_page=15
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Document::with([
            'transactions' => fn($q) => $q->latest()->limit(1),
            'recipients:document_no,office_id,office_name,recipient_type,sequence',
            'signatories:document_no,employee_id,employee_name,office_name',
        ])
            ->where('office_id', $user->office_id)   // All docs from this office
            ->where('isActive', true);

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Keyword search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('document_no',    'like', "%{$search}%")
                    ->orWhere('subject',       'like', "%{$search}%")
                    ->orWhere('document_type', 'like', "%{$search}%")
                    ->orWhere('action_type',   'like', "%{$search}%");
            });
        }

        // Document type filter
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        // Recipient office filter
        if ($request->filled('recipient_office_id')) {
            $query->whereHas('recipients', function ($q) use ($request) {
                $q->where('office_id', $request->recipient_office_id);
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $documents = $query
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json($documents);
    }

    /**
     * GET /api/documents/filters
     *
     * Returns distinct document_types and recipient offices
     * that actually exist in the current user's OFFICE documents.
     * Drives the filter dropdowns — no hardcoding needed on the frontend.
     */
    public function filters(Request $request): JsonResponse
    {
        $user = $request->user();

        $officeDocumentNos = Document::where('office_id', $user->office_id)
            ->where('isActive', true)
            ->select('document_no');

        $documentTypes = Document::where('office_id', $user->office_id)
            ->where('isActive', true)
            ->distinct()
            ->orderBy('document_type')
            ->pluck('document_type')
            ->filter()
            ->values();

        $recipientOffices = DocumentRecipient::whereIn('document_no', $officeDocumentNos)
            ->select('office_id', 'office_name')
            ->distinct('office_id')
            ->get()
            ->unique('office_id')
            ->values();

        return response()->json([
            'document_types'    => $documentTypes,
            'recipient_offices' => $recipientOffices,
        ]);
    }


    /**
     * GET /api/documents/received
     *
     * Returns all transactions where the authenticated user's office
     * is listed as a recipient in document_recipients.
     *
     * Query params (all optional):
     *   ?search=keyword     → searches subject, document_no, document_type
     *   ?per_page=15
     */
    public function received(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = DocumentTransaction::with([
            'document',
            'recipients:id,document_no,transaction_no,office_id,office_name,recipient_type,sequence',
            'signatories:id,document_no,transaction_no,employee_id,employee_name,office_name',
        ])
            // Only transactions where this office is a recipient
            ->whereHas('recipients', function ($q) use ($user) {
                $q->where('office_id', $user->office_id);
            })
            // Exclude transactions originated by this office (those belong in My Documents)
            ->where('office_id', '!=', $user->office_id)
            ->where('status', 'Processing');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject',       'like', "%{$search}%")
                    ->orWhere('document_no', 'like', "%{$search}%")
                    ->orWhere('document_type', 'like', "%{$search}%");
            });
        }

        $transactions = $query
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json($transactions);
    }
}
