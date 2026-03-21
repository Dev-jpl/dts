<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\OfficeLibrary;
use App\Models\User;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    /**
     * Check if user can manage office settings
     */
    private function canManageOffice(Request $request): bool
    {
        $user = $request->user();
        // For now, allow if user has role 'admin' or 'superior'
        // This can be expanded based on actual role implementation
        return in_array($user->role ?? 'user', ['admin', 'superior', 'head']);
    }

    /**
     * Get office profile
     * GET /api/settings/office
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $office = OfficeLibrary::find($user->office_id);

        if (!$office) {
            return response()->json([
                'success' => false,
                'message' => 'Office not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $office->id,
                'office_name' => $office->office_name,
                'office_code' => $office->office_code ?? null,
                'description' => $office->description ?? null,
                'isActive' => $office->isActive ?? true,
            ],
        ]);
    }

    /**
     * Update office profile
     * PUT /api/settings/office
     */
    public function update(Request $request)
    {
        if (!$this->canManageOffice($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorized to manage office settings.',
            ], 403);
        }

        $request->validate([
            'office_name' => 'required|string|max:150',
            'office_code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $office = OfficeLibrary::find($user->office_id);

        if (!$office) {
            return response()->json([
                'success' => false,
                'message' => 'Office not found.',
            ], 404);
        }

        $office->update([
            'office_name' => $request->input('office_name'),
            'office_code' => $request->input('office_code'),
            'description' => $request->input('description'),
        ]);

        // Update office_name in user's record too
        $user->update(['office_name' => $request->input('office_name')]);

        return response()->json([
            'success' => true,
            'message' => 'Office profile updated successfully.',
            'data' => $office,
        ]);
    }

    /**
     * Get office members
     * GET /api/settings/office/members
     */
    public function members(Request $request)
    {
        $user = $request->user();

        $members = User::where('office_id', $user->office_id)
            ->where('isActive', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->fullName(),
                    'email' => $member->email,
                    'role' => $member->role ?? 'user',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    /**
     * Get office defaults
     * GET /api/settings/office/defaults
     */
    public function defaults(Request $request)
    {
        $user = $request->user();
        $office = OfficeLibrary::find($user->office_id);

        if (!$office) {
            return response()->json([
                'success' => false,
                'message' => 'Office not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'default_urgency_level' => $office->default_urgency_level ?? 'High',
                'default_routing_type' => $office->default_routing_type ?? 'Single',
            ],
        ]);
    }

    /**
     * Update office defaults
     * PUT /api/settings/office/defaults
     */
    public function updateDefaults(Request $request)
    {
        if (!$this->canManageOffice($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorized to manage office settings.',
            ], 403);
        }

        $request->validate([
            'default_urgency_level' => 'nullable|in:Urgent,High,Normal,Routine',
            'default_routing_type' => 'nullable|in:Single,Multiple,Sequential',
        ]);

        $user = $request->user();
        $office = OfficeLibrary::find($user->office_id);

        if (!$office) {
            return response()->json([
                'success' => false,
                'message' => 'Office not found.',
            ], 404);
        }

        $office->update([
            'default_urgency_level' => $request->input('default_urgency_level', 'High'),
            'default_routing_type' => $request->input('default_routing_type', 'Single'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Office defaults updated successfully.',
            'data' => [
                'default_urgency_level' => $office->default_urgency_level,
                'default_routing_type' => $office->default_routing_type,
            ],
        ]);
    }
}
