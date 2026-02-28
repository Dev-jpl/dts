<?php

// ================================================================
// UPDATED TransactionController.php — Full action methods
// with TransactionStatusService integrated.
//
// Changes from previous version:
//  1. releaseDocument → sets status to 'Processing'
//  2. receiveDocument → calls TransactionStatusService::evaluate()
//  3. Both return updated transaction status in response
//
// Also includes the show() fix: uncomment 'logs' eager load
// ================================================================

namespace App\Http\Controllers;

use App\Events\DocumentActivityLoggedEvent;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\DocumentComment;
use App\Services\TransactionStatusService;
use App\Models\DocumentTransaction;
use App\Models\DocumentTransactionLog;
use App\Models\DocumentRecipient;
use App\Models\DocumentSignatory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $documentNo    = 'DOC-' . strtoupper(Str::uuid()->toString());
        $transactionNo = 'TRX-' . strtoupper(Str::uuid()->toString());

        $user = $request->user();


        DB::beginTransaction();

        // return response()->json([
        //     'payload' => $payload,
        // ], 500);

        try {
            // Document
            Document::create([
                'document_no'     => $documentNo,
                'document_type'   => $payload['documentType']['code'],
                'action_type'     => $payload['actionTaken']['action'],
                'origin_type'     => $payload['originType'],
                'subject'         => $payload['subject'],
                'remarks'         => $payload['remarks'],
                'status'          => 'Draft',
                'office_id'       => $user->office_id ?? null,
                'office_name'     => $user->office_name ?? null,
                'created_by_id'   => $user->id,
                'created_by_name' => $user->fullName(),
                'isActive'        => true,
            ]);

            // Transaction
            DocumentTransaction::create([
                'transaction_no'  => $transactionNo,
                'transaction_type' => 'Default',
                'routing'     => $payload['routing'] ?? 'Single',
                'document_no'     => $documentNo,
                'document_type'   => $payload['documentType']['code'],
                'action_type'     => $payload['actionTaken']['action'],
                'origin_type'     => $payload['originType'],
                'subject'         => $payload['subject'],
                'remarks'         => $payload['remarks'],
                'status'          => 'Draft',
                'office_id'       => $user->office_id ?? null,
                'office_name'     => $user->office_name ?? null,
                'created_by_id'   => $user->id,
                'created_by_name' => $user->fullName(),
                'isActive'        => true,
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
            if (isset($payload['signatories'])) {
                foreach ($payload['signatories'] as $signatory) {
                    DocumentSignatory::create([
                        'document_no'     => $documentNo,
                        'transaction_no'  => $transactionNo,
                        'employee_id'   => $signatory['id'],
                        'employee_name'   => $signatory['name'],
                        'office_name'     => $signatory['office'],
                        'office_id'       => $signatory['office_id'],
                    ]);
                }
            }

            // Attachments
            // $attachments = array_merge($payload['files'], $payload['attachments']);
            // foreach ($attachments as $file) {
            //     DocumentAttachment::create([
            //         'document_no'    => $documentNo,
            //         'transaction_no' => $transactionNo,
            //         'file_name'      => $file['name'],
            //         'attachment_type' => 'main',
            //         'office_id'      => $user->office_id ?? null,
            //         'office_name'    => $user->office_name ?? null,
            //     ]);
            // }

            // Attachments (files + attachments merged)
            $allFiles = array_merge(
                array_map(fn($f) => array_merge($f, ['attachment_type' => 'main']), $payload['files'] ?? []),
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
                    'office_id'       => $user->office_id ?? null,
                    'office_name'     => $user->office_name ?? null,
                    'created_by_id'   => $user->id,
                    'created_by_name' => $user->fullName(),
                ]);
            }

            //Log profile transaction
            $profilePayload = [
                'document_no'    => $documentNo,
                'transaction_no' => $transactionNo,
                'status'        => 'Profiled',
                'action_taken'   => $payload['actionTaken']['action'],
                'remarks'       => $payload['remarks'],
                // pass the authenticated user model
                'user'          => $user,
                // normalize office info to an array with explicit id/name/type
                'office'        => [
                    'id'          => $user->office_id ?? null,
                    'office_name' => $user->office_name ?? null,
                    'office_type' => $user->office_type ?? null,
                ],
                'routing_type' => $payload['routing'],
                'released_to'   => null,
                'activity'      => null,
            ];

            // Dispatch the event
            event(new DocumentActivityLoggedEvent($profilePayload));

            // Commit if everything succeeds
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully.',
                'data' => [
                    'document_no'    => $documentNo,
                    'transaction_no' => $transactionNo,
                ]
            ], 201);
        } catch (\Throwable $e) {
            // Rollback if anything fails
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction. All changes rolled back.',
                'error'   => $e->getMessage(),
                'hint'    => 'Check payload structure and database constraints.',
                'trace'   => config('app.debug') ? $e->getTrace() : null,

            ], 500);
        }
    }

    public function show_logs($trxNo)
    {
        $logs = DocumentTransactionLog::where('transaction_no', $trxNo)
            ->orderBy('created_at', 'desc')
            ->get();

        $grouped = $logs->groupBy(function ($log) {
            return $log->created_at->format('j M, Y'); // e.g. "6 Aug, 2023"
        })->map(function ($logs, $date) {
            return [
                'date' => $date,
                'logs' => $logs->map(function ($log) {
                    // Normalize routed office(s)
                    $routedTo = [];
                    if (!empty($log->routed_office_id) && !empty($log->routed_office_name)) {
                        $routedTo[] = [
                            'receivingOffice' => $log->routed_office_name,
                            'department'      => $log->routed_office_service ?? null,
                            'id'              => $log->routed_office_id,
                        ];
                    }

                    return [
                        'activity'          => $log->status,
                        'descriptiveAction' => $log->action_taken,
                        'remarks'           => $log->remarks,
                        'service'           => $log->office_service ?? null,
                        'office'            => $log->office_name,
                        'assignedPresonnel' => $log->assigned_personnel_name,
                        'office_code'       => $log->office_id,
                        'time'              => $log->created_at->format('g:i A'),
                        'transactingOffice' => [
                            'service'            => $log->office_service ?? null,
                            'office'             => $log->office_name,
                            'routedTo'           => $routedTo,
                            'multipleRecipients' => count($routedTo) > 1,
                            // optionally add assignedRecipients, archivedFrom, etc.
                        ],
                    ];
                }),
            ];
        })->values();

        return response()->json($grouped);
    }

    public function getComments(string $trxNo): JsonResponse
    {
        $comments = DocumentComment::where('transaction_no', $trxNo)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $comments,
        ]);
    }

    public function postComment(Request $request, string $trxNo): JsonResponse
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

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

        return response()->json([
            'success' => true,
            'data'    => $comment,
        ], 201);
    }

    // ────────────────────────────────────────────────────────────
    // show() — UNCOMMENT 'logs' so frontend has full log data
    // ────────────────────────────────────────────────────────────
    public function show(string $trxNo): JsonResponse
    {
        $transaction = DocumentTransaction::with([
            'document',
            'recipients',
            'signatories',
            'attachments',
            'logs',          // ← WAS COMMENTED OUT — UNCOMMENT THIS
        ])
            ->where('transaction_no', $trxNo)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $transaction,
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // releaseDocument — updated to set status = 'Processing'
    // ────────────────────────────────────────────────────────────
    public function releaseDocument(Request $request, string $trxNo): JsonResponse
    {
        $validated = $request->validate([
            'remarks'       => 'nullable|string',
            'routed_office' => 'nullable|array',
        ]);

        $user        = $request->user();
        $transaction = DocumentTransaction::with(['document', 'logs', 'recipients'])
            ->where('transaction_no', $trxNo)
            ->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        // Guard: prevent double-release
        $alreadyReleased = $transaction->logs->where('status', 'Released')->isNotEmpty();
        if ($alreadyReleased) {
            return response()->json([
                'success' => false,
                'message' => 'Document has already been released.',
            ], 409);
        }

        DB::transaction(function () use ($trxNo, $transaction, $validated, $user) {
            // 1. Create the Released log
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

            // 2. Always set status to Processing on release
            $transaction->update(['status' => 'Processing']);
        });

        // Return updated transaction so frontend can refresh
        $transaction->refresh()->load(['document', 'recipients', 'signatories', 'attachments', 'logs']);

        return response()->json([
            'success' => true,
            'message' => 'Document released successfully.',
            'data'    => $transaction,
        ], 201);
    }

    // ────────────────────────────────────────────────────────────
    // receiveDocument — updated with TransactionStatusService
    // ────────────────────────────────────────────────────────────
    public function receiveDocument(Request $request, string $trxNo): JsonResponse
    {
        $user      = $request->user();
        $validated = $request->validate([
            'remarks' => 'nullable|string|max:255',
        ]);

        $transaction = DocumentTransaction::with(['recipients', 'logs'])
            ->where('transaction_no', $trxNo)
            ->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        // Guard: must be Released first
        if ($transaction->logs->where('status', 'Released')->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Document has not been released yet.',
            ], 422);
        }

        // Guard: not completed
        if ($transaction->status === 'Completed') {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is already completed.',
            ], 422);
        }

        // Guard: must be a registered default recipient
        $isRecipient = $transaction->recipients
            ->where('office_id', $user->office_id)
            ->where('recipient_type', 'default')
            ->isNotEmpty();

        if (!$isRecipient) {
            return response()->json([
                'success' => false,
                'message' => 'Your office is not a recipient of this document.',
            ], 403);
        }

        // Guard: no duplicate receive
        if ($transaction->logs->where('status', 'Received')->where('office_id', $user->office_id)->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your office has already received this document.',
            ], 409);
        }

        // Guard: Sequential — must be active step
        if (strtolower($transaction->routing) === 'sequential') {
            $sorted = $transaction->recipients
                ->where('recipient_type', 'default')
                ->sortBy('sequence');

            $activeRecipient = null;
            foreach ($sorted as $r) {
                if ($transaction->logs->where('status', 'Received')->where('office_id', $r->office_id)->isEmpty()) {
                    $activeRecipient = $r;
                    break;
                }
            }

            if (!$activeRecipient || $activeRecipient->office_id !== $user->office_id) {
                return response()->json([
                    'success' => false,
                    'message' => "It is not your office's turn. Sequential order must be followed.",
                ], 422);
            }
        }

        DB::transaction(function () use ($trxNo, $transaction, $validated, $user) {
            // 1. Create the Received log
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

            // 2. Evaluate and update transaction status
            TransactionStatusService::evaluate($trxNo);
        });

        // Return the fully updated transaction
        $transaction->refresh()->load(['document', 'recipients', 'signatories', 'attachments', 'logs']);

        return response()->json([
            'success' => true,
            'message' => 'Document received successfully.',
            'data'    => $transaction,
        ], 201);
    }

    // ────────────────────────────────────────────────────────────
    // returnToSender — updated with TransactionStatusService
    // ────────────────────────────────────────────────────────────
    public function returnToSender(Request $request, string $trxNo): JsonResponse
    {
        $user      = $request->user();
        $validated = $request->validate([
            'remarks' => 'required|string|max:255',
        ]);

        $transaction = DocumentTransaction::with(['recipients', 'logs'])
            ->where('transaction_no', $trxNo)
            ->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        // Guard: must have received first
        $hasReceived = $transaction->logs
            ->where('status', 'Received')
            ->where('office_id', $user->office_id)
            ->isNotEmpty();

        if (!$hasReceived) {
            return response()->json([
                'success' => false,
                'message' => 'You must receive the document before returning it.',
            ], 422);
        }

        // Guard: not already returned
        $alreadyReturned = $transaction->logs
            ->where('status', 'Returned To Sender')
            ->where('office_id', $user->office_id)
            ->isNotEmpty();

        if ($alreadyReturned) {
            return response()->json([
                'success' => false,
                'message' => 'Your office has already returned this document.',
            ], 409);
        }

        DB::transaction(function () use ($trxNo, $transaction, $validated, $user) {
            DocumentTransactionLog::create([
                'transaction_no'          => $trxNo,
                'document_no'             => $transaction->document_no,
                'status'                  => 'Returned To Sender',
                'action_taken'            => $transaction->action_type,
                'activity'                => "Document returned to sender by {$user->office_name}",
                'remarks'                 => $validated['remarks'],
                'assigned_personnel_id'   => $user->id,
                'assigned_personnel_name' => $user->fullName(),
                'office_id'               => $user->office_id,
                'office_name'             => $user->office_name,
                'routed_office_id'        => $transaction->office_id,   // back to origin
                'routed_office_name'      => $transaction->office_name,
            ]);

            // Evaluate — for Single/Sequential a return may still trigger Completed
            // (per design: a returned doc means that recipient is "done")
            TransactionStatusService::evaluate($trxNo);
        });

        $transaction->refresh()->load(['document', 'recipients', 'signatories', 'attachments', 'logs']);

        return response()->json([
            'success' => true,
            'message' => 'Document returned to sender successfully.',
            'data'    => $transaction,
        ], 201);
    }

    public function forwardDocument(Request $request, string $trxNo): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'routed_office'             => 'required|array',
            'routed_office.id'          => 'required|string',
            'routed_office.office_name' => 'required|string',
            'action'                    => 'required|array',
            'action.action'             => 'required|string',  // the action label
            'remarks'                   => 'nullable|string|max:255',
        ]);

        $transaction = DocumentTransaction::with(['recipients', 'logs'])
            ->where('transaction_no', $trxNo)
            ->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        // Guard: must have Received first
        $hasReceived = $transaction->logs
            ->where('status', 'Received')
            ->where('office_id', $user->office_id)
            ->isNotEmpty();

        if (!$hasReceived) {
            return response()->json([
                'success' => false,
                'message' => 'You must receive the document before forwarding it.',
            ], 422);
        }

        // Guard: not already forwarded by this office
        $alreadyForwarded = $transaction->logs
            ->where('status', 'Forwarded')
            ->where('office_id', $user->office_id)
            ->isNotEmpty();

        if ($alreadyForwarded) {
            return response()->json([
                'success' => false,
                'message' => 'Your office has already forwarded this document.',
            ], 409);
        }

        // Guard: transaction must not be completed
        if ($transaction->status === 'Completed') {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is already completed.',
            ], 422);
        }

        $routedOffice = $validated['routed_office'];
        $action       = $validated['action'];

        DB::transaction(function () use ($trxNo, $transaction, $validated, $user, $routedOffice, $action) {

            // 1. Log the Forward
            DocumentTransactionLog::create([
                'transaction_no'          => $trxNo,
                'document_no'             => $transaction->document_no,
                'status'                  => 'Forwarded',
                'action_taken'            => $action['action'],  // new action for next office
                'activity'                => "Document forwarded to {$routedOffice['office_name']} by {$user->office_name}",
                'remarks'                 => $validated['remarks'] ?? null,
                'assigned_personnel_id'   => $user->id,
                'assigned_personnel_name' => $user->fullName(),
                'office_id'               => $user->office_id,
                'office_name'             => $user->office_name,
                'routed_office_id'        => $routedOffice['id'],
                'routed_office_name'      => $routedOffice['office_name'],
            ]);

            // 2. Mark the current office's recipient row as inactive (they've stepped out)
            DocumentRecipient::where('transaction_no', $trxNo)
                ->where('office_id', $user->office_id)
                ->update(['isActive' => false]);

            // 3. Add the new office as a recipient
            //    Use firstOrCreate to prevent duplicates if re-forwarding to same office
            DocumentRecipient::firstOrCreate(
                [
                    'transaction_no' => $trxNo,
                    'office_id'      => $routedOffice['id'],
                ],
                [
                    'document_no'     => $transaction->document_no,
                    'recipient_type'  => 'default',
                    'office_name'     => $routedOffice['office_name'],
                    'sequence'        => null,   // forward is not sequential
                    'created_by_id'   => $user->id,
                    'created_by_name' => $user->fullName(),
                    'isActive'        => true,
                ]
            );

            // 4. Re-activate in case it was a previous recipient that was deactivated
            DocumentRecipient::where('transaction_no', $trxNo)
                ->where('office_id', $routedOffice['id'])
                ->update(['isActive' => true]);

            // Status stays Processing — new office must Receive
            $transaction->update(['status' => 'Processing']);
        });

        $transaction->refresh()->load(['document', 'recipients', 'signatories', 'attachments', 'logs']);

        return response()->json([
            'success' => true,
            'message' => 'Document forwarded successfully.',
            'data'    => $transaction,
        ], 201);
    }
}
