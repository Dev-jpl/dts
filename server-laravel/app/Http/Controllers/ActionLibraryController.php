<?php

namespace App\Http\Controllers;

use App\Models\ActionLibrary;
use Illuminate\Http\JsonResponse;

class ActionLibraryController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $actions = ActionLibrary::where('isActive', true)
                ->orderBy('name')
                ->get();

            $mapped = $actions->map(function ($action) {
                return [
                    'item_id'    => $action->id,
                    'item_title' => $action->name,
                    'item_desc'  => $action->type === 'FA' ? 'For Action' : 'For Information',
                    'return_value' => [
                        'id'                   => $action->id,
                        'action'               => $action->name,
                        'type'                 => $action->type,
                        'reply_is_terminal'    => $action->reply_is_terminal,
                        'requires_proof'       => $action->requires_proof,
                        'proof_description'    => $action->proof_description,
                        'default_urgency_level'=> $action->default_urgency_level,
                    ],
                ];
            });

            return response()->json($mapped, 200);
        } catch (\Throwable $th) {
            \Log::error('ActionLibraryController@index failed', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load action library.',
            ], 500);
        }
    }
}
