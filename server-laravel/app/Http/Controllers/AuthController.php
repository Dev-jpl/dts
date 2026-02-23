<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // Register
    public function register(Request $request)
    {
        $request->validate([
            'id' => 'required|string|unique:users',
            'email' => 'required|email|unique:users',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'id' => $request->id,
            'email' => $request->email,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'office_id' => $request->office_id,
            'office_name' => $request->office_name,
            'isActive' => true,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    // Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);


        if (Auth::attempt($request->only('email', 'password'))) {
            $user = $request->user();

            $token = $user->createToken('auth_token')->accessToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ]);
        }

        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    // public function login(Request $request)
    // {
    //     $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required'],]);
    //     if (Auth::attempt($credentials)) {
    //         $request->session()->regenerate();
    //         return response()->json(['message' => 'Logged in']);
    //     }
    //     return response()->json(['message' => 'Invalid credentials'], 401);
    // }



    // Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    // Get Authenticated User
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
