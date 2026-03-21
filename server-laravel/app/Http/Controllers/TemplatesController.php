<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateRecipient;
use App\Models\DocumentTemplateSignatory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TemplatesController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Listing
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/templates
     * All templates visible to the authenticated user (personal + office + system).
     */
    public function index(Request $request): JsonResponse
    {
        $user    = $request->user();
        $perPage = (int) $request->query('per_page', 15);
        $search  = $request->query('search');

        $query = DocumentTemplate::visible($user)
            ->where('isActive', true)
            ->with(['recipients', 'signatories'])
            ->orderBy('updated_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('document_type', 'like', "%{$search}%")
                  ->orWhere('action_type', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($perPage),
        ]);
    }

    /**
     * GET /api/templates/personal
     * Only templates created by the authenticated user.
     */
    public function personal(Request $request): JsonResponse
    {
        $user    = $request->user();
        $perPage = (int) $request->query('per_page', 15);

        $data = DocumentTemplate::where('scope', 'personal')
            ->where('created_by_id', $user->id)
            ->where('isActive', true)
            ->with(['recipients', 'signatories'])
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * GET /api/templates/office
     * Office-scoped templates for the authenticated user's office.
     */
    public function office(Request $request): JsonResponse
    {
        $user    = $request->user();
        $perPage = (int) $request->query('per_page', 15);

        $data = DocumentTemplate::where('scope', 'office')
            ->where('office_id', $user->office_id)
            ->where('isActive', true)
            ->with(['recipients', 'signatories'])
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * GET /api/templates/system
     * System-wide templates (visible to all users).
     */
    public function system(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);

        $data = DocumentTemplate::where('scope', 'system')
            ->where('isActive', true)
            ->with(['recipients', 'signatories'])
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/templates/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $template = DocumentTemplate::visible($user)->with(['recipients', 'signatories'])->find($id);

        if (!$template) {
            return response()->json(['success' => false, 'message' => 'Template not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $template]);
    }

    /**
     * POST /api/templates
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'             => 'required|string|max:150',
            'description'      => 'nullable|string',
            'scope'            => 'required|in:personal,office,system',
            'document_type'    => 'nullable|string|max:100',
            'action_type'      => 'nullable|string|max:100',
            'routing_type'     => 'nullable|in:Single,Multiple,Sequential',
            'urgency_level'    => 'nullable|in:Urgent,High,Normal,Routine',
            'origin_type'      => 'nullable|string|max:100',
            'sender'           => 'nullable|string|max:150',
            'sender_position'  => 'nullable|string|max:150',
            'sender_office'    => 'nullable|string|max:150',
            'sender_email'     => 'nullable|email|max:255',
            'remarks_template' => 'nullable|string',
            'recipients'       => 'nullable|array',
            'recipients.*.office_id'      => 'required_with:recipients|string',
            'recipients.*.office_name'    => 'required_with:recipients|string|max:150',
            'recipients.*.recipient_type' => 'nullable|in:default,cc,bcc',
            'recipients.*.sequence'       => 'nullable|integer|min:1',
            'signatories'                 => 'nullable|array',
            'signatories.*.signatory_id'  => 'nullable|string',
            'signatories.*.name'          => 'required_with:signatories|string|max:150',
            'signatories.*.position'      => 'nullable|string|max:150',
            'signatories.*.office'        => 'nullable|string|max:150',
            'signatories.*.role'          => 'nullable|in:Noted,Approved,Signed,Certified',
            'signatories.*.sequence'      => 'nullable|integer|min:1',
        ]);

        $authError = $this->checkScopePermission($user, $validated['scope']);
        if ($authError) {
            return $authError;
        }

        $template = DB::transaction(function () use ($validated, $user) {
            $template = DocumentTemplate::create([
                'name'             => $validated['name'],
                'description'      => $validated['description'] ?? null,
                'scope'            => $validated['scope'],
                'document_type'    => $validated['document_type'] ?? null,
                'action_type'      => $validated['action_type'] ?? null,
                'routing_type'     => $validated['routing_type'] ?? null,
                'urgency_level'    => $validated['urgency_level'] ?? 'High',
                'origin_type'      => $validated['origin_type'] ?? null,
                'sender'           => $validated['sender'] ?? null,
                'sender_position'  => $validated['sender_position'] ?? null,
                'sender_office'    => $validated['sender_office'] ?? null,
                'sender_email'     => $validated['sender_email'] ?? null,
                'remarks_template' => $validated['remarks_template'] ?? null,
                'created_by_id'    => $user->id,
                'created_by_name'  => $user->fullName(),
                'office_id'        => $user->office_id,
                'isActive'         => true,
                'use_count'        => 0,
            ]);

            $this->syncRecipients($template, $validated['recipients'] ?? []);
            $this->syncSignatories($template, $validated['signatories'] ?? []);

            return $template;
        });

        return response()->json([
            'success' => true,
            'message' => 'Template created.',
            'data'    => $template->load(['recipients', 'signatories']),
        ], 201);
    }

    /**
     * PUT /api/templates/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $template = DocumentTemplate::find($id);

        if (!$template) {
            return response()->json(['success' => false, 'message' => 'Template not found.'], 404);
        }

        $editError = $this->checkEditPermission($user, $template);
        if ($editError) {
            return $editError;
        }

        $validated = $request->validate([
            'name'             => 'sometimes|required|string|max:150',
            'description'      => 'nullable|string',
            'scope'            => 'sometimes|required|in:personal,office,system',
            'document_type'    => 'nullable|string|max:100',
            'action_type'      => 'nullable|string|max:100',
            'routing_type'     => 'nullable|in:Single,Multiple,Sequential',
            'urgency_level'    => 'nullable|in:Urgent,High,Normal,Routine',
            'origin_type'      => 'nullable|string|max:100',
            'sender'           => 'nullable|string|max:150',
            'sender_position'  => 'nullable|string|max:150',
            'sender_office'    => 'nullable|string|max:150',
            'sender_email'     => 'nullable|email|max:255',
            'remarks_template' => 'nullable|string',
            'recipients'       => 'nullable|array',
            'recipients.*.office_id'      => 'required_with:recipients|string',
            'recipients.*.office_name'    => 'required_with:recipients|string|max:150',
            'recipients.*.recipient_type' => 'nullable|in:default,cc,bcc',
            'recipients.*.sequence'       => 'nullable|integer|min:1',
            'signatories'                 => 'nullable|array',
            'signatories.*.signatory_id'  => 'nullable|string',
            'signatories.*.name'          => 'required_with:signatories|string|max:150',
            'signatories.*.position'      => 'nullable|string|max:150',
            'signatories.*.office'        => 'nullable|string|max:150',
            'signatories.*.role'          => 'nullable|in:Noted,Approved,Signed,Certified',
            'signatories.*.sequence'      => 'nullable|integer|min:1',
        ]);

        if (isset($validated['scope']) && $validated['scope'] !== $template->scope) {
            $authError = $this->checkScopePermission($user, $validated['scope']);
            if ($authError) {
                return $authError;
            }
        }

        DB::transaction(function () use ($template, $validated) {
            $template->update(array_filter($validated, fn($v, $k) => !in_array($k, ['recipients', 'signatories']), ARRAY_FILTER_USE_BOTH));

            if (array_key_exists('recipients', $validated)) {
                $this->syncRecipients($template, $validated['recipients'] ?? []);
            }
            if (array_key_exists('signatories', $validated)) {
                $this->syncSignatories($template, $validated['signatories'] ?? []);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Template updated.',
            'data'    => $template->refresh()->load(['recipients', 'signatories']),
        ]);
    }

    /**
     * DELETE /api/templates/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $template = DocumentTemplate::find($id);

        if (!$template) {
            return response()->json(['success' => false, 'message' => 'Template not found.'], 404);
        }

        $editError = $this->checkEditPermission($user, $template);
        if ($editError) {
            return $editError;
        }

        $template->update(['isActive' => false]);

        return response()->json(['success' => true, 'message' => 'Template deleted.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Actions
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/templates/{id}/duplicate
     * Creates a personal copy — "Copy of [name]" — for the authenticated user.
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $template = DocumentTemplate::visible($user)->with(['recipients', 'signatories'])->find($id);

        if (!$template) {
            return response()->json(['success' => false, 'message' => 'Template not found.'], 404);
        }

        $copy = DB::transaction(function () use ($template, $user) {
            $copy = DocumentTemplate::create([
                'name'             => 'Copy of ' . $template->name,
                'description'      => $template->description,
                'scope'            => 'personal',
                'document_type'    => $template->document_type,
                'action_type'      => $template->action_type,
                'routing_type'     => $template->routing_type,
                'urgency_level'    => $template->urgency_level,
                'origin_type'      => $template->origin_type,
                'sender'           => $template->sender,
                'sender_position'  => $template->sender_position,
                'sender_office'    => $template->sender_office,
                'sender_email'     => $template->sender_email,
                'remarks_template' => $template->remarks_template,
                'created_by_id'    => $user->id,
                'created_by_name'  => $user->fullName(),
                'office_id'        => $user->office_id,
                'isActive'         => true,
                'use_count'        => 0,
            ]);

            foreach ($template->recipients as $r) {
                DocumentTemplateRecipient::create([
                    'template_id'    => $copy->id,
                    'office_id'      => $r->office_id,
                    'office_name'    => $r->office_name,
                    'recipient_type' => $r->recipient_type,
                    'sequence'       => $r->sequence,
                ]);
            }

            foreach ($template->signatories as $s) {
                DocumentTemplateSignatory::create([
                    'template_id'   => $copy->id,
                    'signatory_id'  => $s->signatory_id,
                    'name'          => $s->name,
                    'position'      => $s->position,
                    'office'        => $s->office,
                    'role'          => $s->role,
                    'sequence'      => $s->sequence,
                ]);
            }

            return $copy;
        });

        return response()->json([
            'success' => true,
            'message' => 'Template duplicated.',
            'data'    => $copy->load(['recipients', 'signatories']),
        ], 201);
    }

    /**
     * POST /api/templates/{id}/use
     * Increments use_count, updates last_used_at, returns full template for form prefill.
     */
    public function use(Request $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $template = DocumentTemplate::visible($user)->with(['recipients', 'signatories'])->find($id);

        if (!$template) {
            return response()->json(['success' => false, 'message' => 'Template not found.'], 404);
        }

        $template->increment('use_count');
        $template->update(['last_used_at' => now()]);

        return response()->json([
            'success' => true,
            'data'    => $template->refresh()->load(['recipients', 'signatories']),
        ]);
    }

    /**
     * POST /api/documents/{docNo}/save-as-template
     * Origin office only. Pre-fills from document's latest transaction.
     */
    public function saveAsTemplate(Request $request, string $docNo): JsonResponse
    {
        $user     = $request->user();
        $document = Document::with([
            'transactions' => fn($q) => $q->latest()->limit(1),
            'transactions.recipients' => fn($q) => $q->where('isActive', true)->orderBy('sequence'),
            'transactions.signatories' => fn($q) => $q->where('isActive', true)->orderBy('sequence'),
        ])->where('document_no', $docNo)->first();

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found.'], 404);
        }

        if ($document->office_id !== $user->office_id) {
            return response()->json(['success' => false, 'message' => 'Only the origin office can save as template.'], 403);
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string',
            'scope'       => 'required|in:personal,office,system',
        ]);

        $authError = $this->checkScopePermission($user, $validated['scope']);
        if ($authError) {
            return $authError;
        }

        $transaction = $document->transactions->first();

        $template = DB::transaction(function () use ($validated, $user, $document, $transaction) {
            $template = DocumentTemplate::create([
                'name'             => $validated['name'],
                'description'      => $validated['description'] ?? null,
                'scope'            => $validated['scope'],
                'document_type'    => $document->document_type ?? null,
                'action_type'      => $transaction?->action_type ?? null,
                'routing_type'     => $transaction?->routing_type ?? null,
                'urgency_level'    => $transaction?->urgency_level ?? 'High',
                'origin_type'      => $document->origin_type ?? null,
                'sender'           => $document->sender ?? null,
                'sender_position'  => $document->sender_position ?? null,
                'sender_office'    => $document->sender_office ?? null,
                'sender_email'     => $document->sender_email ?? null,
                'remarks_template' => $transaction?->remarks ?? null,
                'created_by_id'    => $user->id,
                'created_by_name'  => $user->fullName(),
                'office_id'        => $user->office_id,
                'isActive'         => true,
                'use_count'        => 0,
            ]);

            if ($transaction) {
                foreach ($transaction->recipients as $r) {
                    DocumentTemplateRecipient::create([
                        'template_id'    => $template->id,
                        'office_id'      => $r->office_id,
                        'office_name'    => $r->office_name,
                        'recipient_type' => $r->recipient_type,
                        'sequence'       => $r->sequence,
                    ]);
                }

                foreach ($transaction->signatories as $s) {
                    DocumentTemplateSignatory::create([
                        'template_id'   => $template->id,
                        'signatory_id'  => $s->signatory_id ?? null,
                        'name'          => $s->name,
                        'position'      => $s->position ?? null,
                        'office'        => $s->office ?? null,
                        'role'          => $s->role ?? null,
                        'sequence'      => $s->sequence,
                    ]);
                }
            }

            return $template;
        });

        return response()->json([
            'success' => true,
            'message' => 'Template saved.',
            'data'    => $template->load(['recipients', 'signatories']),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function checkScopePermission($user, string $scope): ?JsonResponse
    {
        if ($scope === 'system' && $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Only admins can create system templates.'], 403);
        }

        if ($scope === 'office' && !in_array($user->role, ['superior', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Only office heads or admins can create office templates.'], 403);
        }

        return null;
    }

    private function checkEditPermission($user, DocumentTemplate $template): ?JsonResponse
    {
        if ($template->scope === 'personal' && $template->created_by_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Not authorized.'], 403);
        }

        if ($template->scope === 'office') {
            if ($template->office_id !== $user->office_id || !in_array($user->role, ['superior', 'admin'])) {
                return response()->json(['success' => false, 'message' => 'Not authorized.'], 403);
            }
        }

        if ($template->scope === 'system' && $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Not authorized.'], 403);
        }

        return null;
    }

    private function syncRecipients(DocumentTemplate $template, array $recipients): void
    {
        $template->recipients()->delete();

        foreach ($recipients as $index => $r) {
            DocumentTemplateRecipient::create([
                'template_id'    => $template->id,
                'office_id'      => $r['office_id'],
                'office_name'    => $r['office_name'],
                'recipient_type' => $r['recipient_type'] ?? 'default',
                'sequence'       => $r['sequence'] ?? ($index + 1),
            ]);
        }
    }

    private function syncSignatories(DocumentTemplate $template, array $signatories): void
    {
        $template->signatories()->delete();

        foreach ($signatories as $index => $s) {
            DocumentTemplateSignatory::create([
                'template_id'   => $template->id,
                'signatory_id'  => $s['signatory_id'] ?? null,
                'name'          => $s['name'],
                'position'      => $s['position'] ?? null,
                'office'        => $s['office'] ?? null,
                'role'          => $s['role'] ?? 'Signed',
                'sequence'      => $s['sequence'] ?? ($index + 1),
            ]);
        }
    }
}
