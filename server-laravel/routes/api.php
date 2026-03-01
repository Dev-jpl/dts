<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\IncomingController;
use App\Http\Controllers\OfficeLibraryController;
use App\Http\Controllers\SignatoriesLibraryController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\EnsureClientIsResourceOwner;

Route::middleware(['auth:api'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', fn(Request $request) => $request->user());

    Route::post('/upload/temp', [TransactionController::class, 'tempUpload']);

    // ─────────────────────────────────────────────────────────────────────
    // Transaction routes
    // ─────────────────────────────────────────────────────────────────────
    Route::prefix('/transactions')->group(function () {
        Route::post('/create',                  [TransactionController::class, 'store']);
        Route::get('/{trxNo}/show',             [TransactionController::class, 'show']);
        Route::get('/{trxNo}/history',          [TransactionController::class, 'show_logs']);

        // Actions
        Route::post('/{trxNo}/release',             [TransactionController::class, 'releaseDocument']);
        Route::post('/{trxNo}/subsequent-release',  [TransactionController::class, 'subsequentRelease']);
        Route::post('/{trxNo}/receive',             [TransactionController::class, 'receiveDocument']);
        Route::post('/{trxNo}/done',                [TransactionController::class, 'markAsDone']);
        Route::post('/{trxNo}/forward',             [TransactionController::class, 'forwardDocument']);
        Route::post('/{trxNo}/return',              [TransactionController::class, 'returnToSender']);
        Route::post('/{trxNo}/reply',               [TransactionController::class, 'reply']);
        Route::patch('/{trxNo}/recipients',         [TransactionController::class, 'manageRecipients']);

        // Deprecated comment endpoints (kept for backward compat)
        Route::get('/{trxNo}/comments',         [TransactionController::class, 'getComments']);
        Route::post('/{trxNo}/comments',        [TransactionController::class, 'postComment']);

        // Legacy stubs
        Route::post('/{trxNo}/log_transaction', [TransactionController::class, 'storeLog']);
        Route::post('/{trxNo}/upload/commit',   [TransactionController::class, 'commitUpload']);
    });

    // ─────────────────────────────────────────────────────────────────────
    // Document routes
    // ─────────────────────────────────────────────────────────────────────
    Route::prefix('documents')->group(function () {
        // Listing endpoints (specific paths first to avoid conflicts with {docNo})
        Route::get('/',           [DocumentController::class, 'index']);
        Route::get('/filters',    [DocumentController::class, 'filters']);
        Route::get('/received',   [DocumentController::class, 'received']);
        Route::post('/close-bulk', [DocumentController::class, 'closeBulk']);

        // Document-level actions
        Route::post('/{docNo}/close',       [DocumentController::class, 'close']);
        Route::put('/{docNo}/re-release',   [DocumentController::class, 'reRelease']);
        Route::post('/{docNo}/copy',        [DocumentController::class, 'copy']);
        Route::get('/{docNo}/notes',        [DocumentController::class, 'getNotes']);
        Route::post('/{docNo}/notes',       [DocumentController::class, 'postNote']);
    });

    // ─────────────────────────────────────────────────────────────────────
    // Library routes
    // ─────────────────────────────────────────────────────────────────────
    Route::prefix('library')->group(function () {
        Route::get('/offices',     [OfficeLibraryController::class, 'index']);
        Route::get('/signatories', [SignatoriesLibraryController::class, 'index']);
    });

    // ─────────────────────────────────────────────────────────────────────
    // Incoming module
    // ─────────────────────────────────────────────────────────────────────
    Route::prefix('incoming')->group(function () {
        Route::get('/',            [IncomingController::class, 'index']);
        Route::get('/for-action',  [IncomingController::class, 'forAction']);
        Route::get('/overdue',     [IncomingController::class, 'overdue']);
        Route::get('/in-progress', [IncomingController::class, 'inProgress']);
        Route::get('/completed',   [IncomingController::class, 'completed']);
        Route::get('/closed',      [IncomingController::class, 'closed']);
        Route::get('/counts',      [IncomingController::class, 'counts']);
        Route::get('/filters',     [IncomingController::class, 'filters']);
    });
});

Route::get('/machine-data', function () {
    return response()->json(['message' => 'Client credentials token is valid.']);
})->middleware(EnsureClientIsResourceOwner::class);

Route::get('/user', fn(Request $request) => $request->user())->middleware(EnsureClientIsResourceOwner::class);
Route::post('/login', [AuthController::class, 'login'])->name('login');
