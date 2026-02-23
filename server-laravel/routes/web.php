<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::get('/debug-auth', function (Request $request) {
    return response()->json(
        [
            'request' => $request,
            'user' => auth()->user(),
            'guard' => auth()->getDefaultDriver(),
            'session_has_user' => session()->has('_login_web'),
            'session' => session()->all(),
        ],
        200
    );
})->middleware(['web']);

// Protected routes (require Sanctum cookie/session)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});
