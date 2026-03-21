<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentNote;
use App\Models\DocumentRecipient;
use App\Models\DocumentSignatory;
use App\Models\DocumentTransaction;
use App\Models\DocumentTransactionLog;
use App\Services\NotificationService;
use App\Services\TransactionStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DocumentController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // My Documents
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/documents
     * Returns documents created by the authenticated user's office.
     * Supports status, search, document_type, recipient_office_id, date range filters.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Document::with([
            'transactions' => fn($q) => $q->latest()->limit(1),
            'recipients:document_no,office_id,office_name,recipient_type,sequence',
            'signatories:document_no,employee_id,employee_name,office_name',
        ])
            ->where('office_id', $user->office_id)
            ->where('isActive', true);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('document_no',    'like', "%{$search}%")
                    ->orWhere('subject',       'like', "%{$search}%")
                    ->orWhere('document_type', 'like', "%{$search}%")
                    ->orWhere('action_type',   'like', "%{$search}%");
            });
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('recipient_office_id')) {
            $query->whereHas('recipients', fn($q) => $q->where('office_id', $request->recipient_office_id));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return response()->json(
            $query->orderBy('created_at', 'desc')->paginate($request->integer('per_page', 15))
        );
    }

    /**
     * GET /api/documents/filters
     * Returns distinct document types and recipient offices for filter dropdowns.
     */
    public function filters(Request $request): JsonResponse
    {
        $user = $request->user();

        $officeDocNos = Document::where('office_id', $user->office_id)
            ->where('isActive', true)
            ->select('document_no');

        $documentTypes = Document::where('office_id', $user->office_id)
            ->where('isActive', true)
            ->distinct()->orderBy('document_type')
            ->pluck('document_type')->filter()->values();

        $recipientOffices = DocumentRecipient::whereIn('document_no', $officeDocNos)
            ->select('office_id', 'office_name')
            ->distinct('office_id')->get()
            ->unique('office_id')->values();

        return response()->json([
            'document_types'    => $documentTypes,
            'recipient_offices' => $recipientOffices,
        ]);
    }

    /**
     * GET /api/documents/received
     * Returns transactions where this office is a recipient.
     */
    public function received(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = DocumentTransaction::with([
            'document',
            'recipients:id,document_no,transaction_no,office_id,office_name,recipient_type,sequence',
            'signatories:id,document_no,transaction_no,employee_id,employee_name,office_name',
        ])
            ->whereHas('recipients', fn($q) => $q->where('office_id', $user->office_id))
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

        return response()->json(
            $query->orderBy('created_at', 'desc')->paginate($request->integer('per_page', 15))
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Close (single)
    // POST /api/documents/{docNo}/close
    // ─────────────────────────────────────────────────────────────────────────

    public function close(Request $request, string $docNo): JsonResponse
    {
        $validated = $request->validate([
            'remarks' => 'required|string|max:255',
        ]);

        $user     = $request->user();
        $document = Document::with(['transactions.recipients', 'transactions.logs'])
            ->where('document_no', $docNo)->first();

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found.'], 404);
        }

        // Guard: origin office only
        if ($document->office_id !== $user->office_id) {
            return response()->json(['success' => false, 'message' => 'Only the originating office can close a document.'], 403);
        }

        if (in_array($document->status, ['Draft', 'Closed'])) {
            return response()->json(['success' => false, 'message' => "Cannot close a document with status '{$document->status}'."], 422);
        }

        // Collect pending recipients across all Processing transactions for notification
        $pendingRecipients = collect();

        DB::transaction(function () use ($docNo, $document, $validated, $user, &$pendingRecipients) {
            foreach ($document->transactions as $transaction) {
                if ($transaction->status !== 'Processing') {
                    continue;
                }

                // Deactivate all remaining active recipients
                $active = $transaction->recipients->where('isActive', true);
                foreach ($active as $r) {
                    DocumentRecipient::where('transaction_no', $transaction->transaction_no)
                        ->where('office_id', $r->office_id)
                        ->update(['isActive' => false]);

                    $pendingRecipients->push($r);
                }

                // Closed log on the transaction
                DocumentTransactionLog::create([
                    'transaction_no'          => $transaction->transaction_no,
                    'document_no'             => $docNo,
                    'status'                  => 'Closed',
                    'action_taken'            => $transaction->action_type,
                    'activity'                => "Document closed by {$user->office_name}",
                    'remarks'                 => $validated['remarks'],
                    'assigned_personnel_id'   => $user->id,
                    'assigned_personnel_name' => $user->fullName(),
                    'office_id'               => $user->office_id,
                    'office_name'             => $user->office_name,
                ]);

                $transaction->update(['status' => 'Completed']);
            }

            $document->update(['status' => 'Closed']);
        });

        if ($pendingRecipients->isNotEmpty()) {
            NotificationService::onForceClose($document, $pendingRecipients);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document closed successfully.',
            'data'    => $document->refresh()->load('transactions.recipients'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Close Bulk
    // POST /api/documents/close-bulk
    // ─────────────────────────────────────────────────────────────────────────

    public function closeBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_nos' => 'required|array|min:1',
            'document_nos.*' => 'string',
            'remarks'      => 'required|string|max:255',
        ]);

        $user = $request->user();

        $documents = Document::whereIn('document_no', $validated['document_nos'])
            ->where('office_id', $user->office_id)
            ->get();

        // Guard: all must be Completed
        $notCompleted = $documents->where('status', '!=', 'Completed');
        if ($notCompleted->isNotEmpty()) {
            $list = $notCompleted->pluck('document_no')->implode(', ');
            return response()->json([
                'success' => false,
                'message' => "Bulk close only applies to Completed documents. These are not Completed: {$list}",
            ], 422);
        }

        DB::transaction(function () use ($documents, $validated, $user) {
            foreach ($documents as $document) {
                foreach ($document->transactions as $transaction) {
                    DocumentTransactionLog::create([
                        'transaction_no'          => $transaction->transaction_no,
                        'document_no'             => $document->document_no,
                        'status'                  => 'Closed',
                        'action_taken'            => $transaction->action_type,
                        'activity'                => "Document bulk-closed by {$user->office_name}",
                        'remarks'                 => $validated['remarks'],
                        'assigned_personnel_id'   => $user->id,
                        'assigned_personnel_name' => $user->fullName(),
                        'office_id'               => $user->office_id,
                        'office_name'             => $user->office_name,
                    ]);
                }

                $document->update(['status' => 'Closed']);
            }
        });

        return response()->json([
            'success' => true,
            'message' => count($validated['document_nos']) . ' document(s) closed successfully.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Edit & Re-release
    // PUT /api/documents/{docNo}/re-release
    // ─────────────────────────────────────────────────────────────────────────

    public function reRelease(Request $request, string $docNo): JsonResponse
    {
        $validated = $request->validate([
            'subject'       => 'required|string|max:255',
            'remarks'       => 'nullable|string|max:255',
            'recipients'    => 'required|array|min:1',
            'recipients.*.office_id'      => 'required|string',
            'recipients.*.office_name'    => 'required|string',
            'recipients.*.recipient_type' => 'required|in:default,cc,bcc',
            'recipients.*.sequence'       => 'nullable|integer',
            'routing'       => 'required|in:Single,Multiple,Sequential',
        ]);

        $user     = $request->user();
        $document = Document::with(['transactions'])
            ->where('document_no', $docNo)->first();

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found.'], 404);
        }

        // Guard: origin office only, document must be Returned
        if ($document->office_id !== $user->office_id) {
            return response()->json(['success' => false, 'message' => 'Only the originating office can re-release a document.'], 403);
        }

        if ($document->status !== 'Returned') {
            return response()->json(['success' => false, 'message' => 'Only Returned documents can be re-released.'], 422);
        }

        $newTrxNo = 'TRX-' . strtoupper(Str::uuid()->toString());

        DB::transaction(function () use ($docNo, $document, $validated, $user, $newTrxNo) {
            // Snapshot current state to document_versions
            $previousTrx = $document->transactions->sortByDesc('created_at')->first();
            $version     = ($previousTrx ? DB::table('document_versions')
                ->where('document_no', $docNo)->max('version_number') : 0) + 1;

            DB::table('document_versions')->insert([
                'document_no'          => $docNo,
                'transaction_no'       => $previousTrx?->transaction_no ?? $newTrxNo,
                'version_number'       => $version,
                'subject'              => $document->subject,
                'action_type'          => $document->action_type,
                'document_type'        => $document->document_type,
                'origin_type'          => $document->origin_type,
                'remarks'              => $document->remarks,
                'recipients_snapshot'  => json_encode(
                    $previousTrx?->recipients->toArray() ?? []
                ),
                'changed_by_id'        => $user->id,
                'changed_by_name'      => $user->fullName(),
                'changed_at'           => now(),
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            // Update document fields
            $document->update([
                'subject' => $validated['subject'],
                'remarks' => $validated['remarks'] ?? $document->remarks,
                'status'  => 'Active',
            ]);

            // New transaction for this re-release
            DocumentTransaction::create([
                'transaction_no'        => $newTrxNo,
                'transaction_type'      => 'Default',
                'parent_transaction_no' => $previousTrx?->transaction_no,
                'routing'               => $validated['routing'],
                'document_no'           => $docNo,
                'document_type'         => $document->document_type,
                'action_type'           => $document->action_type,
                'origin_type'           => $document->origin_type,
                'subject'               => $validated['subject'],
                'remarks'               => $validated['remarks'] ?? null,
                'status'                => 'Processing',
                'office_id'             => $user->office_id,
                'office_name'           => $user->office_name,
                'created_by_id'         => $user->id,
                'created_by_name'       => $user->fullName(),
                'isActive'              => true,
            ]);

            // Recipients for new transaction
            foreach ($validated['recipients'] as $r) {
                DocumentRecipient::create([
                    'document_no'     => $docNo,
                    'transaction_no'  => $newTrxNo,
                    'recipient_type'  => $r['recipient_type'],
                    'office_id'       => $r['office_id'],
                    'office_name'     => $r['office_name'],
                    'sequence'        => $r['sequence'] ?? null,
                    'created_by_id'   => $user->id,
                    'created_by_name' => $user->fullName(),
                    'isActive'        => true,
                ]);
            }

            // Released + Document Revised logs
            DocumentTransactionLog::create([
                'transaction_no'          => $newTrxNo,
                'document_no'             => $docNo,
                'status'                  => 'Document Revised',
                'action_taken'            => $document->action_type,
                'activity'                => "Document revised and re-released by {$user->office_name}",
                'assigned_personnel_id'   => $user->id,
                'assigned_personnel_name' => $user->fullName(),
                'office_id'               => $user->office_id,
                'office_name'             => $user->office_name,
            ]);

            DocumentTransactionLog::create([
                'transaction_no'          => $newTrxNo,
                'document_no'             => $docNo,
                'status'                  => 'Released',
                'action_taken'            => $document->action_type,
                'activity'                => "Document re-released by {$user->office_name}",
                'assigned_personnel_id'   => $user->id,
                'assigned_personnel_name' => $user->fullName(),
                'office_id'               => $user->office_id,
                'office_name'             => $user->office_name,
            ]);
        });

        $newTrx = DocumentTransaction::with(['document', 'recipients', 'signatories', 'attachments', 'logs'])
            ->where('transaction_no', $newTrxNo)->first();

        NotificationService::onRelease($newTrx);

        return response()->json([
            'success' => true,
            'message' => 'Document revised and re-released successfully.',
            'data'    => $newTrx,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Copy to New Document
    // POST /api/documents/{docNo}/copy
    // ─────────────────────────────────────────────────────────────────────────

    public function copy(Request $request, string $docNo): JsonResponse
    {
        $validated = $request->validate([
            'subject'    => 'nullable|string|max:255',  // override subject, or use original
            'remarks'    => 'nullable|string|max:255',
            'recipients' => 'nullable|array',
            'recipients.*.office_id'      => 'required_with:recipients|string',
            'recipients.*.office_name'    => 'required_with:recipients|string',
            'recipients.*.recipient_type' => 'required_with:recipients|in:default,cc,bcc',
            'recipients.*.sequence'       => 'nullable|integer',
        ]);

        $user     = $request->user();
        $document = Document::with(['transactions.recipients', 'transactions.signatories'])
            ->where('document_no', $docNo)->first();

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found.'], 404);
        }

        // Guard: origin office only
        if ($document->office_id !== $user->office_id) {
            return response()->json(['success' => false, 'message' => 'Only the originating office can copy a document.'], 403);
        }

        // Guard: document must have allow_copy=true
        if (!$document->allow_copy) {
            return response()->json(['success' => false, 'message' => 'This document is not marked as copyable.'], 403);
        }

        $newDocNo  = 'DOC-' . strtoupper(Str::uuid()->toString());
        $newTrxNo  = 'TRX-' . strtoupper(Str::uuid()->toString());

        DB::transaction(function () use ($docNo, $newDocNo, $newTrxNo, $document, $validated, $user) {
            $qrCode = base64_encode(
                QrCode::format('svg')->size(200)->generate(
                    config('app.url') . '/view/' . $newDocNo
                )
            );

            Document::create([
                'document_no'     => $newDocNo,
                'document_type'   => $document->document_type,
                'action_type'     => $document->action_type,
                'origin_type'     => $document->origin_type,
                'subject'         => $validated['subject'] ?? $document->subject,
                'remarks'         => $validated['remarks'] ?? $document->remarks,
                'status'          => 'Draft',
                'qr_code'         => $qrCode,
                'allow_copy'      => $document->allow_copy,
                'office_id'       => $user->office_id,
                'office_name'     => $user->office_name,
                'created_by_id'   => $user->id,
                'created_by_name' => $user->fullName(),
                'isActive'        => true,
            ]);

            // Source transaction for recipient/signatory copy
            $sourceTrx = $document->transactions->sortByDesc('created_at')->first();

            DocumentTransaction::create([
                'transaction_no'        => $newTrxNo,
                'transaction_type'      => 'Default',
                'parent_transaction_no' => $sourceTrx?->transaction_no,
                'routing'               => $sourceTrx?->routing ?? 'Single',
                'document_no'           => $newDocNo,
                'document_type'         => $document->document_type,
                'action_type'           => $document->action_type,
                'origin_type'           => $document->origin_type,
                'subject'               => $validated['subject'] ?? $document->subject,
                'remarks'               => $validated['remarks'] ?? $document->remarks,
                'status'                => 'Draft',
                'office_id'             => $user->office_id,
                'office_name'           => $user->office_name,
                'created_by_id'         => $user->id,
                'created_by_name'       => $user->fullName(),
                'isActive'              => true,
            ]);

            // Use provided recipients, or copy from source transaction
            $recipientList = $validated['recipients']
                ?? $sourceTrx?->recipients->map(fn($r) => [
                    'office_id'      => $r->office_id,
                    'office_name'    => $r->office_name,
                    'recipient_type' => $r->recipient_type,
                    'sequence'       => $r->sequence,
                ])->toArray()
                ?? [];

            foreach ($recipientList as $r) {
                DocumentRecipient::create([
                    'document_no'     => $newDocNo,
                    'transaction_no'  => $newTrxNo,
                    'recipient_type'  => $r['recipient_type'],
                    'office_id'       => $r['office_id'],
                    'office_name'     => $r['office_name'],
                    'sequence'        => $r['sequence'] ?? null,
                    'created_by_id'   => $user->id,
                    'created_by_name' => $user->fullName(),
                    'isActive'        => true,
                ]);
            }

            // Copy signatories
            foreach ($sourceTrx?->signatories ?? [] as $sig) {
                DocumentSignatory::create([
                    'document_no'   => $newDocNo,
                    'transaction_no' => $newTrxNo,
                    'employee_id'   => $sig->employee_id,
                    'employee_name' => $sig->employee_name,
                    'office_name'   => $sig->office_name,
                    'office_id'     => $sig->office_id,
                ]);
            }

            // Profiled log
            DocumentTransactionLog::create([
                'transaction_no'          => $newTrxNo,
                'document_no'             => $newDocNo,
                'status'                  => 'Profiled',
                'action_taken'            => $document->action_type,
                'activity'                => "Copied from {$docNo} by {$user->office_name}",
                'assigned_personnel_id'   => $user->id,
                'assigned_personnel_name' => $user->fullName(),
                'office_id'               => $user->office_id,
                'office_name'             => $user->office_name,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Document copied successfully.',
            'data'    => [
                'document_no'    => $newDocNo,
                'transaction_no' => $newTrxNo,
            ],
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Official Notes
    // GET  /api/documents/{docNo}/notes
    // POST /api/documents/{docNo}/notes
    // ─────────────────────────────────────────────────────────────────────────

    public function getNotes(string $docNo): JsonResponse
    {
        $document = Document::where('document_no', $docNo)->first();

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found.'], 404);
        }

        $notes = DocumentNote::where('document_no', $docNo)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['success' => true, 'data' => $notes]);
    }

    public function postNote(Request $request, string $docNo): JsonResponse
    {
        $validated = $request->validate([
            'note'           => 'required|string',
            'transaction_no' => 'required|string|exists:document_transactions,transaction_no',
        ]);

        $user     = $request->user();
        $document = Document::with(['transactions.recipients'])
            ->where('document_no', $docNo)->first();

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found.'], 404);
        }

        if ($document->status === 'Closed') {
            return response()->json(['success' => false, 'message' => 'Cannot add notes to a closed document.'], 422);
        }

        // Guard: user must be an active participant (origin or active recipient)
        $isOrigin = $document->office_id === $user->office_id;
        $isActiveRecipient = $document->transactions->flatMap(fn($t) => $t->recipients)
            ->where('office_id', $user->office_id)
            ->where('isActive', true)
            ->isNotEmpty();

        if (!$isOrigin && !$isActiveRecipient) {
            return response()->json(['success' => false, 'message' => 'Only active participants can add official notes.'], 403);
        }

        $note = DocumentNote::create([
            'document_no'     => $docNo,
            'transaction_no'  => $validated['transaction_no'],
            'note'            => $validated['note'],
            'office_id'       => $user->office_id,
            'office_name'     => $user->office_name,
            'created_by_id'   => $user->id,
            'created_by_name' => $user->fullName(),
        ]);

        NotificationService::onOfficialNoteAdded($document->document_no);

        return response()->json(['success' => true, 'data' => $note], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Received Documents - Enhanced with Stats and Export
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/documents/received/stats
     * Returns stats for received documents within date range.
     */
    public function receivedStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // Base query for received documents in period
        $baseQuery = fn() => DocumentTransactionLog::where('office_id', $user->office_id)
            ->where('status', 'Received')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $totalReceived = $baseQuery()->count();

        // Get unique transaction_nos for completed check
        $receivedTrxNos = $baseQuery()->pluck('transaction_no')->unique();

        // Count completed (transactions that have terminal action after receive)
        $completed = DocumentTransactionLog::whereIn('transaction_no', $receivedTrxNos)
            ->whereIn('status', ['Done', 'Forwarded', 'Returned To Sender'])
            ->where('office_id', $user->office_id)
            ->distinct('transaction_no')
            ->count('transaction_no');

        // Count pending (received but no terminal action yet)
        $pending = $totalReceived - $completed;

        // On-time vs overdue - check due dates
        $onTime = 0;
        $overdue = 0;

        $transactions = DocumentTransaction::whereIn('transaction_no', $receivedTrxNos)->get();
        foreach ($transactions as $trx) {
            $completedLog = DocumentTransactionLog::where('transaction_no', $trx->transaction_no)
                ->whereIn('status', ['Done', 'Forwarded', 'Returned To Sender'])
                ->where('office_id', $user->office_id)
                ->first();

            if ($completedLog && $trx->due_date) {
                if ($completedLog->created_at <= $trx->due_date) {
                    $onTime++;
                } else {
                    $overdue++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_received' => $totalReceived,
                'completed' => $completed,
                'pending' => $pending,
                'on_time' => $onTime,
                'overdue' => $overdue,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ],
        ]);
    }

    /**
     * GET /api/documents/received/export
     * Export received documents to Excel.
     */
    public function receivedExport(Request $request)
    {
        $user = $request->user();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $data = DocumentTransactionLog::with(['transaction.document'])
            ->where('office_id', $user->office_id)
            ->where('status', 'Received')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($log) => [
                'document_no' => $log->document_no,
                'transaction_no' => $log->transaction_no,
                'subject' => $log->transaction?->subject ?? '',
                'document_type' => $log->transaction?->document_type ?? '',
                'action_type' => $log->transaction?->action_type ?? '',
                'from_office' => $log->transaction?->office_name ?? '',
                'received_at' => $log->created_at->format('Y-m-d H:i:s'),
                'status' => $log->transaction?->status ?? '',
            ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total' => $data->count(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Released Documents
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/documents/released
     * Returns documents released by this office.
     */
    public function released(Request $request): JsonResponse
    {
        $user = $request->user();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = DocumentTransaction::with([
            'document',
            'recipients:id,document_no,transaction_no,office_id,office_name,recipient_type,sequence,isActive',
            'logs' => fn($q) => $q->whereIn('status', ['Released', 'Received', 'Done', 'Forwarded', 'Returned To Sender'])->orderBy('created_at'),
        ])
            ->where('office_id', $user->office_id)
            ->whereIn('status', ['Processing', 'Completed', 'Returned']);

        // Date range filter on release date
        if ($startDate && $endDate) {
            $query->whereHas('logs', fn($q) => $q->where('status', 'Released')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('document_no', 'like', "%{$search}%")
                    ->orWhere('document_type', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->orderBy('created_at', 'desc')->paginate($request->integer('per_page', 15))
        );
    }

    /**
     * GET /api/documents/released/stats
     * Returns stats for released documents within date range.
     */
    public function releasedStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // Base query for released documents in period
        $baseQuery = fn() => DocumentTransactionLog::where('office_id', $user->office_id)
            ->where('status', 'Released')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $totalReleased = $baseQuery()->count();

        // Get unique transaction_nos
        $releasedTrxNos = $baseQuery()->pluck('transaction_no')->unique();

        // Count by transaction status
        $transactions = DocumentTransaction::whereIn('transaction_no', $releasedTrxNos)->get();

        $completed = $transactions->where('status', 'Completed')->count();
        $processing = $transactions->where('status', 'Processing')->count();
        $returned = $transactions->where('status', 'Returned')->count();

        // Count recipients who have received
        $totalRecipients = DocumentRecipient::whereIn('transaction_no', $releasedTrxNos)
            ->where('recipient_type', 'default')
            ->count();

        $recipientsReceived = DocumentTransactionLog::whereIn('transaction_no', $releasedTrxNos)
            ->where('status', 'Received')
            ->distinct('office_id', 'transaction_no')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_released' => $totalReleased,
                'completed' => $completed,
                'processing' => $processing,
                'returned' => $returned,
                'total_recipients' => $totalRecipients,
                'recipients_received' => $recipientsReceived,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Archived (Closed) Documents
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/documents/archived
     * Returns closed documents where user's office is originator or recipient.
     */
    public function archived(Request $request): JsonResponse
    {
        $user = $request->user();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Document::with([
            'transactions' => fn($q) => $q->latest()->limit(1),
            'transactions.recipients:id,document_no,transaction_no,office_id,office_name,recipient_type,sequence',
        ])
            ->where('status', 'Closed')
            ->where(function ($q) use ($user) {
                $q->where('office_id', $user->office_id)
                    ->orWhereHas('recipients', fn($rq) => $rq->where('office_id', $user->office_id));
            });

        if ($startDate && $endDate) {
            $query->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('document_no', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('document_type', 'like', "%{$search}%")
                    ->orWhere('action_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('origin_office_id')) {
            $query->where('office_id', $request->origin_office_id);
        }

        return response()->json(
            $query->orderBy('updated_at', 'desc')->paginate($request->integer('per_page', 15))
        );
    }

    /**
     * GET /api/documents/archived/stats
     * Returns stats for archived documents within date range.
     */
    public function archivedStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $baseQuery = fn() => Document::where('status', 'Closed')
            ->where(function ($q) use ($user) {
                $q->where('office_id', $user->office_id)
                    ->orWhereHas('recipients', fn($rq) => $rq->where('office_id', $user->office_id));
            })
            ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $totalArchived = $baseQuery()->count();
        $originatedByMe = $baseQuery()->where('office_id', $user->office_id)->count();
        $receivedByMe = $totalArchived - $originatedByMe;

        // Count distinct document types
        $documentTypes = $baseQuery()->distinct()->pluck('document_type')->count();

        // Count those that were completed before closing vs force-closed
        $completedBeforeClose = $baseQuery()->whereHas('transactions', function ($q) {
            $q->whereHas('logs', fn($lq) => $lq->where('status', 'Closed')
                ->where('activity', 'like', '%bulk-closed%'));
        })->count();
        $forceClosed = $totalArchived - $completedBeforeClose;

        return response()->json([
            'success' => true,
            'data' => [
                'total_archived' => $totalArchived,
                'originated_by_me' => $originatedByMe,
                'received_by_me' => $receivedByMe,
                'document_types' => $documentTypes,
                'bulk_closed' => $completedBeforeClose,
                'force_closed' => $forceClosed,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ],
        ]);
    }

    /**
     * GET /api/documents/archived/export
     * Export archived documents data.
     */
    public function archivedExport(Request $request)
    {
        $user = $request->user();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $data = Document::with(['transactions' => fn($q) => $q->latest()->limit(1)])
            ->where('status', 'Closed')
            ->where(function ($q) use ($user) {
                $q->where('office_id', $user->office_id)
                    ->orWhereHas('recipients', fn($rq) => $rq->where('office_id', $user->office_id));
            })
            ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn($doc) => [
                'document_no' => $doc->document_no,
                'subject' => $doc->subject,
                'document_type' => $doc->document_type,
                'action_type' => $doc->action_type,
                'origin_office' => $doc->office_name,
                'created_at' => $doc->created_at->format('Y-m-d H:i:s'),
                'closed_at' => $doc->updated_at->format('Y-m-d H:i:s'),
            ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total' => $data->count(),
            ],
        ]);
    }

    /**
     * GET /api/documents/released/export
     * Export released documents data.
     */
    public function releasedExport(Request $request)
    {
        $user = $request->user();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $data = DocumentTransactionLog::with(['transaction.document', 'transaction.recipients'])
            ->where('office_id', $user->office_id)
            ->where('status', 'Released')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($log) => [
                'document_no' => $log->document_no,
                'transaction_no' => $log->transaction_no,
                'subject' => $log->transaction?->subject ?? '',
                'document_type' => $log->transaction?->document_type ?? '',
                'action_type' => $log->transaction?->action_type ?? '',
                'routing' => $log->transaction?->routing ?? '',
                'recipients' => $log->transaction?->recipients->where('recipient_type', 'default')->pluck('office_name')->implode(', ') ?? '',
                'released_at' => $log->created_at->format('Y-m-d H:i:s'),
                'status' => $log->transaction?->status ?? '',
            ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total' => $data->count(),
            ],
        ]);
    }
}
