<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Passport\Token;

class SessionsController extends Controller
{
    /**
     * Get user's active sessions (OAuth tokens)
     * GET /api/settings/sessions
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $currentTokenId = $user->token()->id;

        $tokens = Token::where('user_id', $user->id)
            ->where('revoked', false)
            ->whereDate('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) use ($currentTokenId) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'created_at' => $token->created_at->toISOString(),
                    'expires_at' => $token->expires_at?->toISOString(),
                    'is_current' => $token->id === $currentTokenId,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $tokens,
        ]);
    }

    /**
     * Revoke a session (OAuth token)
     * DELETE /api/settings/sessions/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $currentTokenId = $user->token()->id;

        // Prevent revoking current session
        if ($id === $currentTokenId) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot revoke current session. Use logout instead.',
            ], 422);
        }

        $token = Token::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found.',
            ], 404);
        }

        $token->revoke();

        return response()->json([
            'success' => true,
            'message' => 'Session revoked successfully.',
        ]);
    }
}
