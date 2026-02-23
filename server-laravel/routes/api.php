<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\OfficeLibraryController;
use App\Http\Controllers\SignatoriesLibraryController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\EnsureClientIsResourceOwner;

Route::middleware(['auth:api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', fn(Request $request) => $request->user());

    //temp_upload route for testing file uploads, will be removed later
    Route::post('/upload/temp', [TransactionController::class, 'tempUpload']);

    // Transaction routes
    Route::prefix('/transactions')->group(function () {
        Route::post('/create', [TransactionController::class, 'store']);
        Route::get('/{trxNo}/show', [TransactionController::class, 'show']);
        Route::get('/{trxNo}/history', [TransactionController::class, 'show_logs']);
        Route::post('/{trxNo}/log_transaction', [TransactionController::class, 'storeLog']);
        Route::post('/{trxNo}/release', [TransactionController::class, 'releaseDocument']);
        Route::post('/{trxNo}/upload/commit', [TransactionController::class, 'commitUpload']);
        Route::get('/{trxNo}/comments', [TransactionController::class, 'getComments']);    // ← add
        Route::post('/{trxNo}/comments', [TransactionController::class, 'postComment']);   // ← add
    });

    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);   // GET /api/documents  → My Documents
        Route::get('/filters',  [DocumentController::class, 'filters']);
        Route::get('/received',  [DocumentController::class, 'received']);
    });

    // Library routes
    Route::prefix('library')->group(function () {
        Route::get('/offices', [OfficeLibraryController::class, 'index']);
        Route::get('/signatories', [SignatoriesLibraryController::class, 'index']);
    });


    //
});

Route::get('/machine-data', function () {
    return response()->json([
        'message' => 'Client credentials token is valid.'
    ]);
})->middleware(EnsureClientIsResourceOwner::class);

Route::get('/user', fn(Request $request) => $request->user())->middleware(EnsureClientIsResourceOwner::class);
Route::post('/login', [AuthController::class, 'login'])->name('login');
