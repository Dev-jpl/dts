<?php

namespace App\Http\Controllers;

use App\Events\DocumentActivityLoggedEvent;
use App\Http\Requests\StoreTransactionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use App\Models\Document;
use App\Models\DocumentRecipient;
use App\Models\DocumentAttachment;
use App\Models\DocumentLog;
use App\Models\DocumentSignatory;
use App\Models\DocumentTransaction;
use App\Models\DocumentTransactionLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            $attachments = array_merge($payload['files'], $payload['attachments']);
            foreach ($attachments as $file) {
                DocumentAttachment::create([
                    'document_no'    => $documentNo,
                    'transaction_no' => $transactionNo,
                    'file_name'      => $file['name'],
                    'attachment_type' => 'main',
                    'office_id'      => $user->office_id ?? null,
                    'office_name'    => $user->office_name ?? null,
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

            // Log::info('Transaction created payload', ['payload' => $eventPayload]);
            // Display in artisan serve terminal
            // file_put_contents('php://stderr', 'Transaction created payload: ' . json_encode($eventPayload) . PHP_EOL);


            //Log release transaction
            // Decide routed office depending on routing type
            // $routedOffice = $this->get_routed_office($payload['routing'], $transactionNo);
            // $releasePayload = [
            //     'document_no'    => $documentNo,
            //     'transaction_no' => $transactionNo,
            //     'status'        => 'Released',
            //     'action_taken'   => $payload['actionTaken']['action'],
            //     'remarks'       => $payload['remarks'],
            //     // pass the authenticated user model
            //     'user'          => $user,
            //     // normalize office info to an array with explicit id/name/type
            //     'office'        => [
            //         'id'          => $user->office_id ?? null,
            //         'office_name' => $user->office_name ?? null,
            //         'office_type' => $user->office_type ?? null,
            //     ],
            //     'routing_type' => $payload['routing'],
            //     'routed_office' => $routedOffice
            //         ?
            //         [
            //             'id' => $routedOffice->office_id,
            //             'office_name' => $routedOffice->office_name,
            //             'office_type' => $routedOffice->recipient_type,
            //         ]
            //         : null,
            //     'activity'      => null,
            // ];

            // // Dispatch the event
            // event(new DocumentActivityLoggedEvent($releasePayload));


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
            // ❌ Rollback if anything fails
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

    private function get_routed_office(string $routingType, string $transactionNo)
    {
        $recipients = DocumentRecipient::where('transaction_no', $transactionNo)
            ->orderBy('sequence')
            ->get();

        switch ($routingType) {
            case 'single':
                return $recipients->first();

            case 'multiple':
                // Return all recipients, caller can loop and create logs
                return $recipients;

            case 'sequence':
                foreach ($recipients as $recipient) {
                    $alreadyReceived = \App\Models\DocumentTransactionLog::where('transaction_no', $transactionNo)
                        ->where('routed_office_id', $recipient->office_id)
                        ->where('status', 'Received')
                        ->exists();

                    if (!$alreadyReceived) {
                        return $recipient;
                    }
                }
                return null;

            default:
                return null;
        }
    }


    public function show(string $trxNo): JsonResponse
    {
        $transaction = DocumentTransaction::with([
            'document',
            'recipients',
            'signatories',
            'attachments',
            // 'logs',
            // 'comments'
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
            'data' => $transaction
        ]);
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


    public function storeLog(Request $request, $trxNo)
    {
        $user = $request->user();

        $validated = $request->validate(
            [
                'status' => 'required|string|in:Profiled,Received,Released,Archived,Returned To Sender,Forwarded',
                'action_taken' => 'nullable|string|max:100',
                'activity' => 'required|string',
                'remarks' => 'nullable|string',
                'assigned_personnel_id' => 'required|uuid',
                'assigned_personnel_name' => 'required|string|max:150',
                'office_id' => 'required|string|max:50',
                'office_name' => 'required|string|max:150',
            ]
        );
    }


    public function releaseDocument(Request $request, $trxNo)
    {
        $validated = $request->validate([
            'remarks'       => 'nullable|string',
            'routed_office' => 'nullable|array',
        ]);

        $user = $request->user(); // ✅ correct way
        $transaction = DocumentTransaction::with(['document', 'logs'])->where('transaction_no', $trxNo)->first();
        $logs = $transaction->logs;

        $log = DocumentTransactionLog::create([
            'transaction_no'          => $trxNo,
            'document_no'             => $transaction->document_no,
            'status'                  => 'Released',
            'action_taken'            => $transaction->action_type,
            'activity'                => 'Released',
            'remarks'                 => $validated['remarks'] ?? null,
            'assigned_personnel_id'   => $user->id,
            'assigned_personnel_name' => $user->fullName(),
            'office_id'               => $user->office_id,
            'office_name'             => $user->office_name,
            'routed_office_id'        => $validated['routed_office']['id'] ?? null,
            'routed_office_name'      => $validated['routed_office']['office_name'] ?? null,
        ]);

        return response()->json([
            'message' => 'Document released successfully',
            'data'    => $log,
        ], 201);
    }

    public function upload(Request $request, $trxNo)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB limit
        ]);

        $user = $request->user();

        $uploadedFiles = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store("transactions/{$trxNo}", 'public');

            $record = DocumentAttachment::create([
                'transaction_no' => $trxNo,
                'filename'       => $file->getClientOriginalName(),
                'path'           => $path,
                'size'           => $file->getSize(),
                'mime_type'      => $file->getMimeType(),
                'uploaded_by'    => $user->id,
                'office_id'      => $user->office_id,
            ]);

            $uploadedFiles[] = $record;
        }

        return response()->json([
            'message' => 'Files uploaded successfully',
            'data'    => $uploadedFiles,
        ]);
    }

    public function tempUpload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB limit
        ]);

        $tempPaths = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store('temp', 'public'); // save to /storage/app/public/temp
            $tempPaths[] = [
                'original_name' => $file->getClientOriginalName(),
                'temp_path'     => $path,
                'size'          => $file->getSize(),
                'mime_type'     => $file->getMimeType(),
            ];
        }

        return response()->json([
            'message' => 'Files uploaded to temp storage',
            'data'    => $tempPaths,
        ]);
    }

    public function commitUpload(Request $request, $trxNo)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*.temp_path' => 'required|string',
        ]);

        $user = $request->user();
        $savedFiles = [];

        foreach ($request->input('files') as $fileData) {
            $tempPath = $fileData['temp_path'];
            $filename = basename($tempPath);

            // Move file from temp to permanent folder
            $newPath = "transactions/{$trxNo}/{$filename}";
            Storage::disk('public')->move($tempPath, $newPath);

            $record = DocumentAttachment::create([
                'transaction_no' => $trxNo,
                'filename'       => $fileData['original_name'],
                'path'           => $newPath,
                'size'           => $fileData['size'],
                'mime_type'      => $fileData['mime_type'],
                'uploaded_by'    => $user->id,
                'office_id'      => $user->office_id,
            ]);

            $savedFiles[] = $record;
        }

        return response()->json([
            'message' => 'Files committed to permanent storage',
            'data'    => $savedFiles,
        ]);
    }
}
