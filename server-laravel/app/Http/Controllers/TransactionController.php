<?php

namespace App\Http\Controllers;

use App\Events\DocumentActivityLoggedEvent;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\ActionLibrary;
use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\DocumentComment;
use App\Models\DocumentRecipient;
use App\Models\DocumentSignatory;
use App\Models\DocumentTransaction;
use App\Models\DocumentTransactionLog;
use App\Services\NotificationService;
use App\Services\TransactionStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TransactionController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Create
    // ─────────────────────────────────────────────────────────────────────────

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $user    = $request->user();

        $documentNo    = 'DOC-' . strtoupper(Str::uuid()->toString());
        $transactionNo = 'TRX-' . strtoupper(Str::uuid()->toString());

        DB::beginTransaction();

        try {
            // QR code pointing to the public view URL
            $qrCode = base64_encode(
                QrCode::format('svg')->size(200)->generate(
                    config('app.url') . '/view/' . $documentNo
                )
            );

            // Document
            Document::create([
                'document_no'     => $documentNo,
                'document_type'   => $payload['documentType']['code'],
                'action_type'     => $payload['actionTaken']['action'],
                'origin_type'     => $payload['originType'],
                'subject'         => $payload['subject'],
                'remarks'         => $payload['remarks'],
                'status'          => 'Draft',
                'qr_code'         => $qrCode,
                'office_id'       => $user->office_id,
                'office_name'     => $user->office_name,
                'created_by_id'   => $user->id,
                'created_by_name' => $user->fullName(),
                'isActive'        => true,
            ]);

            // Transaction
            DocumentTransaction::create([
                'transaction_no'   => $transactionNo,
                'transaction_type' => 'Default',
                'routing'          => $payload['routing'] ?? 'Single',
                'document_no'      => $documentNo,
                'document_type'    => $payload['documentType']['code'],
                'action_type'      => $payload['actionTaken']['action'],
                'origin_type'      => $payload['originType'],
                'subject'          => $payload['subject'],
                'remarks'          => $payload['remarks'],
                'status'           => 'Draft',
                'office_id'        => $user->office_id,
                'office_name'      => $user->office_name,
                'created_by_id'    => $user->id,
                'created_by_name'  => $user->fullName(),
                'isActive'         => true,
            ]);

            // Recipients
            foreach ($payload['recipients'] as $recipient) {
                DocumentRecipient::create([
                    'document_no'     => $documentNo,
                    'transaction_no'  => $transactionNo,
                    'recipient_type'  => $recipient['recipient_type'],
                    'office_id'       => $recipient['office_code'] ?? null,
                    'office_name'     => $recipient['office'],
                    'sequence'        => $recipient['sequence'],
                    'created_by_id'   => $user->id,
                    'created_by_name' => $user->fullName(),
                    'isActive'        => true,
                ]);
            }

            // Signatories
            foreach ($payload['signatories'] ?? [] as $signatory) {
                DocumentSignatory::create([
                    'document_no'   => $documentNo,
                    'transaction_no' => $transactionNo,
                    'employee_id'   => $signatory['id'],
                    'employee_name' => $signatory['name'],
                    'office_name'   => $signatory['office'],
                    'office_id'     => $signatory['office_id'],
                ]);
            }

            // Attachments
            $allFiles = array_merge(
                array_map(fn($f) => array_merge($f, ['attachment_type' => 'main']),       $payload['files']       ?? []),
                array_map(fn($f) => array_merge($f, ['attachment_type' => 'attachment']), $payload['attachments'] ?? [])
            );

            foreach ($allFiles as $file) {
                $tempPath      = $file['temp_path'];
                $filename      = basename($tempPath);
                $permanentPath = "transactions/{$transactionNo}/{$filename}";

                if (Storage::disk('public')->exists($tempPath)) {
                    Storage::disk('public')->move($tempPath, $permanentPath);
                } else {
                    $permanentPath = $tempPath;
                }

                DocumentAttachment::create([
                    'document_no'     => $documentNo,
                    'transaction_no'  => $transactionNo,
                    'file_name'       => $file['name'],
                    'file_path'       => $permanentPath,
                    'mime_type'       => $file['type'],
                    'file_size'       => $file['size_bytes'],
                    'attachment_type' => $file['attachment_type'],
                    'office_id'       => $user->office_id,
                    'office_name'     => $user->office_name,
                    'created_by_id'   => $user->id,
                    'created_by_name' => $user->fullName(),
                ]);
            }

            // Profiled log
            event(new DocumentActivityLoggedEvent([
                'document_no'    => $documentNo,
                'transaction_no' => $transactionNo,
                'status'         => 'Profiled',
                'action_taken'   => $payload['actionTaken']['action'],
                'remarks'        => $payload['remarks'],
                'user'           => $user,
                'office'         => [
                    'id'          => $user->office_id,
                    'office_name' => $user->office_name,
                    'office_type' => $user->office_type ?? null,
                ],
                'routing_type'   => $payload['routing'],
                'released_to'    => null,
                'activity'       => null,
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully.',
                'data'    => [
                    'document_no'    => $documentNo,
                    'transaction_no' => $transactionNo,
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction.',
                'error'   => $e->getMessage(),
                'trace'   => config('app.debug') ? $e->getTrace() : null,
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Show / History
    // ─────────────────────────────────────────────────────────────────────────

    public function show(string $trxNo): JsonResponse
    {
        $transaction = DocumentTransaction::with([
            'document', 'recipients', 'signatories', 'attachments', 'logs',
        ])->where('transaction_no', $trxNo)->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $transaction]);
    }

    public function show_logs(string $trxNo): JsonResponse
    {
        $logs = DocumentTransactionLog::where('transaction_no', $trxNo)
            ->orderBy('created_at', 'desc')
            ->get();

        $grouped = $logs->groupBy(fn($l) => $l->created_at->format('j M, Y'))
            ->map(fn($dayLogs, $date) => [
                'date' => $date,
                'logs' => $dayLogs->map(fn($log) => [
                    'activity'          => $log->status,
                    'descriptiveAction' => $log->action_taken,
                    'remarks'           => $log->remarks,
                    'reason'            => $log->reason,
                    'office'            => $log->office_name,
                    'assignedPersonnel' => $log->assigned_personnel_name,
                    'office_code'       => $log->office_id,
                    'time'              => $log->created_at->format('g:i A'),
                    'transactingOffice' => [
                        'office'   => $log->office_name,
                        'routedTo' => $log->routed_office_name ? [[
                            'receivingOffice' => $log->routed_office_name,
                            'id'              => $log->routed_office_id,
                        ]] : [],
                    ],
                ]),
            ])->values();

        return response()->json($grouped);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Release (initial)
    // ─────────────────────────────────────────────────────────────────────────

    public function releaseDocument(Request $request, string $trxNo): JsonResponse
    {
        $validated = $request->validate([
            'remarks'       => 'nullable|string|max:255',
            'routed_office' => 'nullable|array',
        ]);

        $user        = $request->user();
        $transaction = DocumentTransaction::with(['document', 'logs', 'recipients'])
            ->where('transaction_no', $trxNo)->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        if ($transaction->logs->where('status', 'Released')->isNotEmpty()) {
            return response()->json(['success' => false, 'message' => 'Document has already been released.'], 409);
        }

        DB::transaction(function () use ($trxNo, $transaction, $validated, $user) {
            DocumentTransactionLog::create([
                'transaction_no'          => $trxNo,
                'document_no'             => $transaction->document_no,
                'status'                  => 'Released',
                'action_taken'            => $transaction->action_type,
                'activity'                => "Document released by {$user->office_name}",
                'remarks'                 => $validated['remarks'] ?? null,
                'assigned_personnel_id'   => $user->id,
                'assigned_personnel_name' => $user->fullName(),
                'office_id'               => $user->office_id,
                'office_name'             => $user->office_name,
                'routed_office_id'        => $validated['routed_office']['id'] ?? null,
                'routed_office_name'      => $validated['routed_office']['office_name'] ?? null,
            ]);

            $transaction->update(['status' => 'Processing']);
            $transaction->document->update(['status' => 'Active']);
        });

        NotificationService::onInitialRelease($transaction->refresh()->load('recipients'));

        return response()->json([
            'success' => true,
            'message' => 'Document released successfully.',
            'data'    => $transaction->refresh()->load(['document', 'recipients', 'signatories', 'attachments', 'logs']),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Subsequent Release
    // POST /{trxNo}/subsequent-release
    // ─────────────────────────────────────────────────────────────────────────

    public function subsequentRelease(Request $request, string $trxNo): JsonResponse
    {
        $validated = $request->validate([
            'target_office_id'   => 'required|string',
            'target_office_name' => 'required|string',
            'remarks'            => 'nullable|string|max:255',
        ]);

        $user        = $request->user();
        $transaction = DocumentTransaction::with(['recipients', 'logs'])
            ->where('transaction_no', $trxNo)->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        if ($transaction->status !== 'Processing') {
            return response()->json(['success' => false, 'message' => 'Transaction is not active.'], 422);
        }

        // Guard: actor must be an active default recipient who has already received
        $actorRecipient = $transaction->recipients
            ->where('office_id', $user->office_id)
            ->where('recipient_type', 'default')
            ->where('isActive', true)
            ->first();

        if (!$actorRecipient) {
            return response()->json(['success' => false, 'message' => 'Your office is not an active recipient of this transaction.'], 403);
        }

        $hasReceived = $transaction->logs
            ->where('office_id', $user->office_id)
            ->where('status', 'Received')
            ->isNotEmpty();

        if (!$hasReceived) {
            return response()->json(['success' => false, 'message' => 'You must receive the document before performing a subsequent release.'], 422);
        }

        // Guard: target must already be a registered recipient on this transaction
        $targetRecipient = $transaction->recipients
            ->where('office_id', $validated['target_office_id'])
            ->first();

        if (!$targetRecipient) {
            return response()->json(['success' => false, 'message' => 'Target office is not a registered recipient on this transaction.'], 422);
        }

        DB::transaction(function () use ($trxNo, $transaction, $validated, $user) {
            // Log the subsequent release
            DocumentTransactionLog::create([
                'transaction_no'          => $trxNo,
                'document_no'             => $transaction->document_no,
                'status'                  => 'Released',
                'action_taken'            => $transaction->action_type,
                'activity'                => "Document subsequently released to {$validated['target_office_name']} by {$user->office_name}",
                'remarks'                 => $validated['remarks'] ?? null,
                'assigned_personnel_id'   => $user->id,
                'assigned_personnel_name' => $user->fullName(),
                'office_id'               => $user->office_id,
                'office_name'             => $user->office_name,
                'routed_office_id'        => $validated['target_office_id'],
                'routed_office_name'      => $validated['target_office_name'],
            ]);

            // Releasing party steps out
            DocumentRecipient::where('transaction_no', $trxNo)
                ->where('office_id', $user->office_id)
                ->update(['isActive' => false]);

            // Target is (re)activated
            DocumentRecipient::where('transaction_no', $trxNo)
                ->where('office_id', $validated['target_office_id'])
                ->update(['isActive' => true]);

            TransactionStatusService::evaluate($trxNo);
        });

        NotificationService::onSubsequentRelease($transaction, $validated['target_office_id'], $validated['target_office_name']);

        return response()->json([
            'success' => true,
            'message' => 'Subsequent release performed successfully.',
            'data'    => $transaction->refresh()->load(['document', 'recipients', 'signatories', 'attachments', 'logs']),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Receive
    // ─────────────────────────────────────────────────────────────────────────

    public function receiveDocument(Request $request, string $trxNo): JsonResponse
    {
        $validated = $request->validate(['remarks' => 'nullable|string|max:255']);
        $user      = $request->user();

        $transaction = DocumentTransaction::with(['recipients', 'logs'])
            ->where('transaction_no', $trxNo)->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        if ($transaction->status !== 'Processing') {
            return response()->json(['success' => false, 'message' => 'Transaction is not active.'], 422);
        }

        // Guard: must be an active recipient (any type)
        $recipient = $transaction->recipients
            ->where('office_id', $user->office_id)
            ->where('isActive', true)
            ->first();

        if (!$recipient) {
            return response()->json(['success' => false, 'message' => 'Your office is not an active recipient of this document.'], 403);
        }

        // Guard: no duplicate receive
        if ($transaction->logs->where('status', 'Received')->where('office_id', $user->office_id)->isNotEmpty()) {
            return response()->json(['success' => false, 'message' => 'Your office has already received this document.'], 409);
        }

        // Guard: sequential turn check (default recipients only)
        if (strtolower($transaction->routing) === 'sequential' && $recipient->recipient_type === 'default') {
            $sorted          = $transaction->recipients->where('recipient_type', 'default')->sortBy('sequence');
            $activeRecipient = null;

            foreach ($sorted as $r) {
                if ($transaction->logs->where('status', 'Received')->where('office_id', $r->office_id)->isEmpty()) {
                    $activeRecipient = $r;
                    break;
                }
            }

            if (!$activeRecipient || $activeRecipient->office_id !== $user->office_id) {
                return response()->json(['success' => false, 'message' => "It is not your office's turn. Sequential order must be followed."], 422);
            }
        }

        DB::transaction(function () use ($trxNo, $transaction, $validated, $user, $recipient) {
            DocumentTransactionLog::create([
                'transaction_no'          => $trxNo,
                'document_no'             => $transaction->document_no,
                'status'                  => 'Received',
                'action_taken'            => $transaction->action_type,
                'activity'                => "Document received by {$user->office_name}",
                'remarks'                 => $validated['remarks'] ?? null,
                'assigned_personnel_id'   => $user->id,
                'assigned_personnel_name' => $user->fullName(),
                'office_id'               => $user->office_id,
                'office_name'             => $user->office_name,
                'routed_office_id'        => null,
                'routed_office_name'      => null,
            ]);

            TransactionStatusService::evaluate($trxNo);
        });

        NotificationService::onReceive($transaction, $user->office_id, $user->office_name, $recipient->recipient_type);

        return response()->json([
            'success' => true,
            'message' => 'Document received successfully.',
            'data'    => $transaction->refresh()->load(['document', 'recipients', 'signatories', 'attachments', 'logs']),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mark as Done
    // POST /{trxNo}/done
    // ─────────────────────────────────────────────────────────────────────────

    public function markAsDone(Request $request, string $trxNo): JsonResponse
    {
        $validated = $request->validate([
            'remarks'       => 'nullable|string|max:255',
            'proof_file_id' => 'nullable|string',  // attachment ID proving completion
        ]);

        $user        = $request->user();
        $transaction = DocumentTransaction::with(['recipients', 'logs'])
            ->where('transaction_no', $trxNo)->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        if ($transaction->status !== 'Processing') {
            return response()->json(['success' => false, 'message' => 'Transaction is not active.'], 422);
        }

        // Guard: FA action type, active default recipient
        $actionLib = ActionLibrary::where('name', $transaction->action_type)->first();

        if (!$actionLib || $actionLib->type !== 'FA') {
            return response()->json(['success' => false, 'message' => 'Mark as Done is only available for For Action (FA) documents.'], 422);
        }

        $recipient = $transaction->recipients
            ->where('office_id', $user->office_id)
            ->where('recipient_type', 'default')
            ->where('isActive', true)
            ->first();

        if (!$recipient) {
            return response()->json(['success' => false, 'message' => 'Your office is not an active default recipient of this transaction.'], 403);
        }

        // Guard: must have received first
        if ($transaction->logs->where('office_id', $user->office_id)->where('status', 'Received')->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'You must receive the document before marking it as done.'], 422);
        }

        // Guard: no duplicate Done
        if ($transaction->logs->where('office_id', $user->office_id)->where('status', 'Done')->isNotEmpty()) {
            return response()->json(['success' => false, 'message' => 'Your office has already marked this document as done.'], 409);
        }

        // Guard: proof required
        if ($actionLib->requires_proof && empty($validated['proof_file_id'])) {
            return response()->json(['success' => false, 'message' => "This action type ({$transaction->action_type}) requires a proof attachment."], 422);
        }

        DB::transaction(function () use ($trxNo, $transaction, $validated, $user) {
            DocumentTransactionLog::create([
                'transaction_no'          => $trxNo,
                'document_no'             => $transaction->document_no,
                'status'                  => 'Done',
                'action_taken'            => $transaction->action_type,
                'activity'                => "Marked as done by {$user->office_name}",
                'remarks'                 => $validated['remarks'] ?? null,
                'assigned_personnel_id'   => $user->id,
                'assigned_personnel_name' => $user->fullName(),
                'office_id'               => $user->office_id,
                'office_name'             => $user->office_name,
            ]);

            // Recipient steps out
            DocumentRecipient::where('transaction_no', $trxNo)
                ->where('office_id', $user->office_id)
                ->update(['isActive' => false]);

            TransactionStatusService::evaluate($trxNo);
        });

        NotificationService::onMarkAsDone($transaction, $user->office_id, $user->office_name);

        return response()->json([
            'success' => true,
            'message' => 'Document marked as done.',
            'data'    => $transaction->refresh()->load(['document', 'recipients', 'signatories', 'attachments', 'logs']),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Forward
    // ─────────────────────────────────────────────────────────────────────────

    public function forwardDocument(Request $request, string $trxNo): JsonResponse
    {
        $validated = $request->validate([
            'routed_office'             => 'required|array',
            'routed_office.id'          => 'required|string',
            'routed_office.office_name' => 'required|string',
            'action'                    => 'required|array',
            'action.action'             => 'required|string',
            'remarks'                   => 'nullable|string|max:255',
        ]);

        $user        = $request->user();
        $transaction = DocumentTransaction::with(['recipients', 'logs'])
            ->where('transaction_no', $trxNo)->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        if ($transaction->status !== 'Processing') {
            return response()->json(['success' => false, 'message' => 'Transaction is not active.'], 422);
        }

        // Guard: active default recipient only
        $recipient = $transaction->recipients
            ->where('office_id', $user->office_id)
            ->where('recipient_type', 'default')
            ->where('isActive', true)
            ->first();

        if (!$recipient) {
            return response()->json(['success' => false, 'message' => 'Your office is not an active default recipient.'], 403);
        }

        // Guard: must have received
        if ($transaction->logs->where('office_id', $user->office_id)->where('status', 'Received')->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'You must receive the document before forwarding.'], 422);
        }

        // Guard: no duplicate forward from this office
        if ($transaction->logs->where('office_id', $user->office_id)->where('status', 'Forwarded')->isNotEmpty()) {
            return response()->json(['success' => false, 'message' => 'Your office has already forwarded this document.'], 409);
        }

        $routedOffice = $validated['routed_office'];

        DB::transaction(function () use ($trxNo, $transaction, $validated, $user, $routedOffice) {
            DocumentTransactionLog::create([
                'transaction_no'          => $trxNo,
                'document_no'             => $transaction->document_no,
                'status'                  => 'Forwarded',
                'action_taken'            => $validated['action']['action'],
                'activity'                => "Document forwarded to {$routedOffice['office_name']} by {$user->office_name}",
                'remarks'                 => $validated['remarks'] ?? null,
                'assigned_personnel_id'   => $user->id,
                'assigned_personnel_name' => $user->fullName(),
                'office_id'               => $user->office_id,
                'office_name'             => $user->office_name,
                'routed_office_id'        => $routedOffice['id'],
                'routed_office_name'      => $routedOffice['office_name'],
            ]);

            // Forwarder steps out permanently
            DocumentRecipient::where('transaction_no', $trxNo)
                ->where('office_id', $user->office_id)
                ->update(['isActive' => false]);

            // Add (or reactivate) the target
            DocumentRecipient::firstOrCreate(
                ['transaction_no' => $trxNo, 'office_id' => $routedOffice['id']],
                [
                    'document_no'     => $transaction->document_no,
                    'recipient_type'  => 'default',
                    'office_name'     => $routedOffice['office_name'],
                    'sequence'        => null,
                    'created_by_id'   => $user->id,
                    'created_by_name' => $user->fullName(),
                    'isActive'        => true,
                ]
            );

            DocumentRecipient::where('transaction_no', $trxNo)
                ->where('office_id', $routedOffice['id'])
                ->update(['isActive' => true]);

            TransactionStatusService::evaluate($trxNo);
        });

        NotificationService::onForward($transaction, $routedOffice['id'], $routedOffice['office_name']);

        return response()->json([
            'success' => true,
            'message' => 'Document forwarded successfully.',
            'data'    => $transaction->refresh()->load(['document', 'recipients', 'signatories', 'attachments', 'logs']),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Return to Sender
    // POST /{trxNo}/return
    // ─────────────────────────────────────────────────────────────────────────

    public function returnToSender(Request $request, string $trxNo): JsonResponse
    {
        $validated = $request->validate([
            'reason'  => 'required|string|max:255',
            'remarks' => 'nullable|string|max:255',
        ]);

        $user        = $request->user();
        $transaction = DocumentTransaction::with(['recipients', 'logs'])
            ->where('transaction_no', $trxNo)->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        if ($transaction->status !== 'Processing') {
            return response()->json(['success' => false, 'message' => 'Transaction is not active.'], 422);
        }

        // Guard: active default recipient only
        $recipient = $transaction->recipients
            ->where('office_id', $user->office_id)
            ->where('recipient_type', 'default')
            ->where('isActive', true)
            ->first();

        if (!$recipient) {
            return response()->json(['success' => false, 'message' => 'Your office is not an active default recipient.'], 403);
        }

        // Guard: must have received
        if ($transaction->logs->where('office_id', $user->office_id)->where('status', 'Received')->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'You must receive the document before returning it.'], 422);
        }

        // Guard: no duplicate return
        if ($transaction->logs->where('office_id', $user->office_id)->where('status', 'Returned To Sender')->isNotEmpty()) {
            return response()->json(['success' => false, 'message' => 'Your office has already returned this document.'], 409);
        }

        // Collect all currently pending recipients (will be halted)
        $pendingRecipients = $transaction->recipients
            ->where('recipient_type', 'default')
            ->where('isActive', true)
            ->where('office_id', '!=', $user->office_id);

        DB::transaction(function () use ($trxNo, $transaction, $validated, $user, $pendingRecipients) {
            // 1. Create Returned To Sender log
            DocumentTransactionLog::create([
                'transaction_no'          => $trxNo,
                'document_no'             => $transaction->document_no,
                'status'                  => 'Returned To Sender',
                'action_taken'            => $transaction->action_type,
                'activity'                => "Document returned to sender by {$user->office_name}",
                'reason'                  => $validated['reason'],
                'remarks'                 => $validated['remarks'] ?? null,
                'assigned_personnel_id'   => $user->id,
                'assigned_personnel_name' => $user->fullName(),
                'office_id'               => $user->office_id,
                'office_name'             => $user->office_name,
                'routed_office_id'        => $transaction->office_id,
                'routed_office_name'      => $transaction->office_name,
            ]);

            // 2. Halt all pending recipients → isActive=false + Routing Halted log
            foreach ($pendingRecipients as $pending) {
                DocumentRecipient::where('transaction_no', $trxNo)
                    ->where('office_id', $pending->office_id)
                    ->update(['isActive' => false]);

                DocumentTransactionLog::create([
                    'transaction_no'          => $trxNo,
                    'document_no'             => $transaction->document_no,
                    'status'                  => 'Routing Halted',
                    'action_taken'            => $transaction->action_type,
                    'activity'                => "Routing halted for {$pending->office_name} due to Return to Sender",
                    'reason'                  => $validated['reason'],
                    'remarks'                 => $validated['remarks'] ?? null,
                    'assigned_personnel_id'   => $user->id,
                    'assigned_personnel_name' => $user->fullName(),
                    'office_id'               => $pending->office_id,
                    'office_name'             => $pending->office_name,
                    'routed_office_id'        => $transaction->office_id,
                    'routed_office_name'      => $transaction->office_name,
                ]);
            }

            // 3. Returning office also steps out
            DocumentRecipient::where('transaction_no', $trxNo)
                ->where('office_id', $user->office_id)
                ->update(['isActive' => false]);

            // 4. Set Transaction → Returned (controller-owned; service respects this)
            $transaction->update(['status' => 'Returned']);

            // 5. Propagate to document
            TransactionStatusService::evaluateDocumentStatus($transaction->document_no);
        });

        NotificationService::onReturnToSender(
            $transaction,
            $user->office_id,
            $user->office_name,
            $validated['reason'],
            $validated['remarks'] ?? null,
            $pendingRecipients
        );

        return response()->json([
            'success' => true,
            'message' => 'Document returned to sender. All pending routing has been halted.',
            'data'    => $transaction->refresh()->load(['document', 'recipients', 'signatories', 'attachments', 'logs']),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reply
    // POST /{trxNo}/reply
    // ─────────────────────────────────────────────────────────────────────────

    public function reply(Request $request, string $trxNo): JsonResponse
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

        $user        = $request->user();
        $transaction = DocumentTransaction::with(['recipients', 'logs'])
            ->where('transaction_no', $trxNo)->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        if ($transaction->status !== 'Processing') {
            return response()->json(['success' => false, 'message' => 'Transaction is not active.'], 422);
        }

        // Guard: must be an active recipient (any type)
        $recipient = $transaction->recipients
            ->where('office_id', $user->office_id)
            ->where('isActive', true)
            ->first();

        if (!$recipient) {
            return response()->json(['success' => false, 'message' => 'Your office is not an active recipient of this transaction.'], 403);
        }

        // Guard: must have received
        if ($transaction->logs->where('office_id', $user->office_id)->where('status', 'Received')->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'You must receive the document before replying.'], 422);
        }

        $actionLib = ActionLibrary::where('name', $transaction->action_type)->first();

        $replyDocNo = 'DOC-' . strtoupper(Str::uuid()->toString());
        $replyTrxNo = 'TRX-' . strtoupper(Str::uuid()->toString());

        DB::transaction(function () use (
            $trxNo, $replyDocNo, $replyTrxNo,
            $transaction, $validated, $user, $recipient, $actionLib
        ) {
            // QR for reply document
            $qrCode = base64_encode(
                QrCode::format('svg')->size(200)->generate(
                    config('app.url') . '/view/' . $replyDocNo
                )
            );

            // 1. New reply document
            Document::create([
                'document_no'     => $replyDocNo,
                'document_type'   => $transaction->document_type,
                'action_type'     => $transaction->action_type,
                'origin_type'     => $transaction->origin_type,
                'subject'         => $validated['subject'],
                'remarks'         => $validated['remarks'] ?? null,
                'status'          => 'Draft',
                'qr_code'         => $qrCode,
                'office_id'       => $user->office_id,
                'office_name'     => $user->office_name,
                'created_by_id'   => $user->id,
                'created_by_name' => $user->fullName(),
                'isActive'        => true,
            ]);

            // 2. New reply transaction
            DocumentTransaction::create([
                'transaction_no'        => $replyTrxNo,
                'transaction_type'      => 'Reply',
                'parent_transaction_no' => $trxNo,
                'routing'               => $validated['routing'],
                'document_no'           => $replyDocNo,
                'document_type'         => $transaction->document_type,
                'action_type'           => $transaction->action_type,
                'origin_type'           => $transaction->origin_type,
                'subject'               => $validated['subject'],
                'remarks'               => $validated['remarks'] ?? null,
                'status'                => 'Draft',
                'office_id'             => $user->office_id,
                'office_name'           => $user->office_name,
                'created_by_id'         => $user->id,
                'created_by_name'       => $user->fullName(),
                'isActive'              => true,
            ]);

            // 3. Recipients on the reply transaction
            foreach ($validated['recipients'] as $r) {
                DocumentRecipient::create([
                    'document_no'     => $replyDocNo,
                    'transaction_no'  => $replyTrxNo,
                    'recipient_type'  => $r['recipient_type'],
                    'office_id'       => $r['office_id'],
                    'office_name'     => $r['office_name'],
                    'sequence'        => $r['sequence'] ?? null,
                    'created_by_id'   => $user->id,
                    'created_by_name' => $user->fullName(),
                    'isActive'        => true,
                ]);
            }

            // 4. If reply_is_terminal: deactivate the replying recipient in the original transaction
            if ($actionLib?->reply_is_terminal && $recipient->recipient_type === 'default') {
                DocumentRecipient::where('transaction_no', $trxNo)
                    ->where('office_id', $user->office_id)
                    ->update(['isActive' => false]);

                // Re-evaluate original transaction (the deactivation may complete it)
                TransactionStatusService::evaluate($trxNo);
            }
        });

        NotificationService::onReply($transaction, $user->office_id, $user->office_name);

        return response()->json([
            'success' => true,
            'message' => 'Reply document created successfully.',
            'data'    => [
                'reply_document_no'    => $replyDocNo,
                'reply_transaction_no' => $replyTrxNo,
                'original_transaction' => $transaction->refresh()->load(['document', 'recipients', 'signatories', 'attachments', 'logs']),
            ],
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Manage Recipients
    // PATCH /{trxNo}/recipients
    // ─────────────────────────────────────────────────────────────────────────

    public function manageRecipients(Request $request, string $trxNo): JsonResponse
    {
        $validated = $request->validate([
            'add'    => 'nullable|array',
            'add.*.office_id'      => 'required_with:add|string',
            'add.*.office_name'    => 'required_with:add|string',
            'add.*.recipient_type' => 'required_with:add|in:default,cc,bcc',
            'add.*.sequence'       => 'nullable|integer',
            'remove' => 'nullable|array',
            'remove.*' => 'string',   // office_ids to remove
            'reorder' => 'nullable|array',
            'reorder.*.office_id' => 'required_with:reorder|string',
            'reorder.*.sequence'  => 'required_with:reorder|integer',
        ]);

        $user        = $request->user();
        $transaction = DocumentTransaction::with(['recipients', 'logs'])
            ->where('transaction_no', $trxNo)->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        if ($transaction->status !== 'Processing') {
            return response()->json(['success' => false, 'message' => 'Transaction is not active.'], 422);
        }

        // Guard: origin office only
        if ($transaction->office_id !== $user->office_id) {
            return response()->json(['success' => false, 'message' => 'Only the originating office can manage recipients.'], 403);
        }

        // Validate removals: cannot remove an office that already Received
        foreach ($validated['remove'] ?? [] as $officeId) {
            $hasReceived = $transaction->logs
                ->where('office_id', $officeId)
                ->where('status', 'Received')
                ->isNotEmpty();

            if ($hasReceived) {
                $officeName = $transaction->recipients
                    ->where('office_id', $officeId)->first()?->office_name ?? $officeId;
                return response()->json([
                    'success' => false,
                    'message' => "{$officeName} has already received this document and cannot be removed.",
                ], 422);
            }
        }

        DB::transaction(function () use ($trxNo, $transaction, $validated, $user) {
            // Add recipients
            foreach ($validated['add'] ?? [] as $r) {
                $existing = $transaction->recipients->where('office_id', $r['office_id'])->first();

                if ($existing) {
                    // Reactivate if previously removed
                    DocumentRecipient::where('transaction_no', $trxNo)
                        ->where('office_id', $r['office_id'])
                        ->update(['isActive' => true, 'sequence' => $r['sequence'] ?? $existing->sequence]);
                } else {
                    DocumentRecipient::create([
                        'document_no'     => $transaction->document_no,
                        'transaction_no'  => $trxNo,
                        'recipient_type'  => $r['recipient_type'],
                        'office_id'       => $r['office_id'],
                        'office_name'     => $r['office_name'],
                        'sequence'        => $r['sequence'] ?? null,
                        'created_by_id'   => $user->id,
                        'created_by_name' => $user->fullName(),
                        'isActive'        => true,
                    ]);
                }

                DocumentTransactionLog::create([
                    'transaction_no'          => $trxNo,
                    'document_no'             => $transaction->document_no,
                    'status'                  => 'Recipient Added',
                    'action_taken'            => $transaction->action_type,
                    'activity'                => "Recipient {$r['office_name']} added by {$user->office_name}",
                    'assigned_personnel_id'   => $user->id,
                    'assigned_personnel_name' => $user->fullName(),
                    'office_id'               => $user->office_id,
                    'office_name'             => $user->office_name,
                    'routed_office_id'        => $r['office_id'],
                    'routed_office_name'      => $r['office_name'],
                ]);
            }

            // Remove recipients (soft only)
            foreach ($validated['remove'] ?? [] as $officeId) {
                $r = $transaction->recipients->where('office_id', $officeId)->first();
                if (!$r) continue;

                DocumentRecipient::where('transaction_no', $trxNo)
                    ->where('office_id', $officeId)
                    ->update(['isActive' => false]);

                DocumentTransactionLog::create([
                    'transaction_no'          => $trxNo,
                    'document_no'             => $transaction->document_no,
                    'status'                  => 'Recipient Removed',
                    'action_taken'            => $transaction->action_type,
                    'activity'                => "Recipient {$r->office_name} removed by {$user->office_name}",
                    'assigned_personnel_id'   => $user->id,
                    'assigned_personnel_name' => $user->fullName(),
                    'office_id'               => $user->office_id,
                    'office_name'             => $user->office_name,
                    'routed_office_id'        => $officeId,
                    'routed_office_name'      => $r->office_name,
                ]);
            }

            // Reorder recipients
            if (!empty($validated['reorder'])) {
                foreach ($validated['reorder'] as $item) {
                    DocumentRecipient::where('transaction_no', $trxNo)
                        ->where('office_id', $item['office_id'])
                        ->update(['sequence' => $item['sequence']]);
                }

                DocumentTransactionLog::create([
                    'transaction_no'          => $trxNo,
                    'document_no'             => $transaction->document_no,
                    'status'                  => 'Recipients Reordered',
                    'action_taken'            => $transaction->action_type,
                    'activity'                => "Recipients reordered by {$user->office_name}",
                    'assigned_personnel_id'   => $user->id,
                    'assigned_personnel_name' => $user->fullName(),
                    'office_id'               => $user->office_id,
                    'office_name'             => $user->office_name,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Recipients updated successfully.',
            'data'    => $transaction->refresh()->load(['document', 'recipients', 'signatories', 'attachments', 'logs']),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Legacy / misc (kept for backward compat)
    // ─────────────────────────────────────────────────────────────────────────

    public function getComments(string $trxNo): JsonResponse
    {
        $comments = DocumentComment::where('transaction_no', $trxNo)
            ->orderBy('created_at', 'asc')->get();

        return response()->json(['success' => true, 'data' => $comments]);
    }

    public function postComment(Request $request, string $trxNo): JsonResponse
    {
        $request->validate(['comment' => 'required|string|max:1000']);
        $user        = $request->user();
        $transaction = DocumentTransaction::where('transaction_no', $trxNo)->firstOrFail();

        $comment = DocumentComment::create([
            'document_no'             => $transaction->document_no,
            'transaction_no'          => $trxNo,
            'office_id'               => $user->office_id,
            'office_name'             => $user->office_name,
            'comment'                 => $request->input('comment'),
            'assigned_personnel_id'   => $user->id,
            'assigned_personnel_name' => $user->fullName(),
        ]);

        return response()->json(['success' => true, 'data' => $comment], 201);
    }

    public function tempUpload(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:20480']);
        $path = $request->file('file')->store('temp', 'public');
        return response()->json(['success' => true, 'temp_path' => $path]);
    }

    public function storeLog(Request $request, string $trxNo): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Use the specific action endpoints instead.'], 410);
    }

    public function commitUpload(Request $request, string $trxNo): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Commit handled inline during create.']);
    }
}
