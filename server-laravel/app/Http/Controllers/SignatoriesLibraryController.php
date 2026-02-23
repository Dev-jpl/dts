<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class SignatoriesLibraryController extends Controller
{
    public function index()
    {
        $users = User::where('isActive', true)->get();

        $items = $users->map(function ($user) {
            return [
                'item_id'    => $user->id,
                'item_title' => $user->first_name . ' ' . $user->last_name,
                'item_desc'  => "{$user->position} - {$user->office_name}",
                'return_value' => [
                    'id'        => $user->id,
                    'name'      => $user->first_name . ' ' . $user->last_name,
                    'position'  => $user->position,
                    'office'    => $user->office_name,
                    'office_id' => $user->office_id,
                ],
            ];
        });

        return response()->json($items, 200);
    }
}
