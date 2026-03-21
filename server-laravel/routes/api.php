<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ActionLibraryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\IncomingController;
use App\Http\Controllers\OfficeLibraryController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SignatoriesLibraryController;
use App\Http\Controllers\TemplatesController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\FileFoldersController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\PreferencesController;
use App\Http\Controllers\Settings\SessionsController;
use App\Http\Controllers\Settings\OfficeController;
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
        Route::delete('/{trxNo}',                   [TransactionController::class, 'destroyDraft']);

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
        
        // Received documents
        Route::get('/received',        [DocumentController::class, 'received']);
        Route::get('/received/stats',  [DocumentController::class, 'receivedStats']);
        Route::get('/received/export', [DocumentController::class, 'receivedExport']);
        
        // Released documents
        Route::get('/released',        [DocumentController::class, 'released']);
        Route::get('/released/stats',  [DocumentController::class, 'releasedStats']);
        Route::get('/released/export', [DocumentController::class, 'releasedExport']);

        // Archived (Closed) documents
        Route::get('/archived',        [DocumentController::class, 'archived']);
        Route::get('/archived/stats',  [DocumentController::class, 'archivedStats']);
        Route::get('/archived/export', [DocumentController::class, 'archivedExport']);

        Route::post('/close-bulk', [DocumentController::class, 'closeBulk']);

        // Document-level actions
        Route::post('/{docNo}/close',             [DocumentController::class,   'close']);
        Route::put('/{docNo}/re-release',         [DocumentController::class,   'reRelease']);
        Route::post('/{docNo}/copy',              [DocumentController::class,   'copy']);
        Route::get('/{docNo}/notes',              [DocumentController::class,   'getNotes']);
        Route::post('/{docNo}/notes',             [DocumentController::class,   'postNote']);
        Route::post('/{docNo}/save-as-template',  [TemplatesController::class,  'saveAsTemplate']);
    });

    // ─────────────────────────────────────────────────────────────────────
    // Templates module
    // ─────────────────────────────────────────────────────────────────────
    Route::prefix('templates')->group(function () {
        // Specific named paths BEFORE parameterized {id} to avoid route conflicts
        Route::get('/personal',         [TemplatesController::class, 'personal']);
        Route::get('/office',           [TemplatesController::class, 'office']);
        Route::get('/system',           [TemplatesController::class, 'system']);
        Route::get('/',                 [TemplatesController::class, 'index']);
        Route::post('/',                [TemplatesController::class, 'store']);
        Route::get('/{id}',             [TemplatesController::class, 'show']);
        Route::put('/{id}',             [TemplatesController::class, 'update']);
        Route::delete('/{id}',          [TemplatesController::class, 'destroy']);
        Route::post('/{id}/duplicate',  [TemplatesController::class, 'duplicate']);
        Route::post('/{id}/use',        [TemplatesController::class, 'use']);
    });

    // ─────────────────────────────────────────────────────────────────────
    // Files management module
    // ─────────────────────────────────────────────────────────────────────
    Route::prefix('files')->group(function () {
        // Folders (before {id} to avoid conflicts)
        Route::get('/folders',          [FileFoldersController::class, 'index']);
        Route::post('/folders',         [FileFoldersController::class, 'store']);
        Route::put('/folders/{id}',     [FileFoldersController::class, 'update']);
        Route::delete('/folders/{id}',  [FileFoldersController::class, 'destroy']);

        // Files
        Route::get('/',                        [FilesController::class, 'index']);
        Route::post('/upload',                 [FilesController::class, 'upload']);
        Route::post('/upload-bulk',            [FilesController::class, 'bulkUpload']);
        Route::get('/{id}',                    [FilesController::class, 'show']);
        Route::get('/{id}/preview',            [FilesController::class, 'preview']);
        Route::get('/{id}/download',           [FilesController::class, 'download']);
        Route::get('/{id}/thumbnail',          [FilesController::class, 'thumbnail']);
        Route::get('/{id}/download-searchable',[FilesController::class, 'downloadSearchable']);
        Route::delete('/{id}',                 [FilesController::class, 'destroy']);
        Route::post('/{id}/reprocess',         [FilesController::class, 'reprocessOcr']);
    });

    // ─────────────────────────────────────────────────────────────────────
    // Library routes
    // ─────────────────────────────────────────────────────────────────────
    Route::prefix('library')->group(function () {
        Route::get('/offices',     [OfficeLibraryController::class, 'index']);
        Route::get('/actions',     [ActionLibraryController::class, 'index']);
        Route::get('/signatories', [SignatoriesLibraryController::class, 'index']);
    });

    // ─────────────────────────────────────────────────────────────────────
    // Dashboard module
    // ─────────────────────────────────────────────────────────────────────
    Route::prefix('dashboard')->group(function () {
        Route::get('/for-action', [DashboardController::class, 'forAction']);
        Route::get('/overdue',    [DashboardController::class, 'overdue']);
        Route::get('/outgoing',   [DashboardController::class, 'outgoing']);
        Route::get('/drafts',     [DashboardController::class, 'drafts']);
        Route::get('/stats',      [DashboardController::class, 'stats']);
        Route::get('/activity',   [DashboardController::class, 'activity']);
        Route::get('/team',       [DashboardController::class, 'team']);
        Route::get('/system',     [DashboardController::class, 'system']);
    });

    // ─────────────────────────────────────────────────────────────────────
    // Reports module
    // ─────────────────────────────────────────────────────────────────────
    Route::prefix('reports')->group(function () {
        Route::get('/office-performance',        [ReportsController::class, 'officePerformance']);
        Route::get('/office-performance/export', [ReportsController::class, 'officePerformanceExport']);
        Route::get('/pipeline',                  [ReportsController::class, 'pipeline']);
        Route::get('/pipeline/export',           [ReportsController::class, 'pipelineExport']);
        Route::get('/compliance',                [ReportsController::class, 'compliance']);
        Route::get('/compliance/export',         [ReportsController::class, 'complianceExport']);
        Route::get('/turnaround',                [ReportsController::class, 'turnaround']);
        Route::get('/turnaround/export',         [ReportsController::class, 'turnaroundExport']);
        // Audit routes must come after the generic export pattern to avoid conflicts
        Route::get('/audit/{docNo}',             [ReportsController::class, 'audit']);
        Route::get('/audit/{docNo}/export',      [ReportsController::class, 'auditExport']);
    });

    // ─────────────────────────────────────────────────────────────────────
    // Search module
    // ─────────────────────────────────────────────────────────────────────
    Route::prefix('search')->group(function () {
        Route::get('/',             [SearchController::class, 'search']);
        Route::get('/quick',        [SearchController::class, 'quick']);
        Route::get('/filters',      [SearchController::class, 'filters']);
        Route::get('/saved',        [SearchController::class, 'savedIndex']);
        Route::post('/saved',       [SearchController::class, 'savedStore']);
        Route::delete('/saved/{id}',[SearchController::class, 'savedDestroy']);
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

    // ─────────────────────────────────────────────────────────────────────
    // Settings module
    // ─────────────────────────────────────────────────────────────────────
    Route::prefix('settings')->group(function () {
        // Profile
        Route::get('/profile',     [ProfileController::class, 'show']);
        Route::put('/profile',     [ProfileController::class, 'update']);
        Route::put('/password',    [ProfileController::class, 'updatePassword']);

        // User preferences
        Route::get('/preferences', [PreferencesController::class, 'show']);
        Route::put('/preferences', [PreferencesController::class, 'update']);

        // Sessions (OAuth tokens)
        Route::get('/sessions',       [SessionsController::class, 'index']);
        Route::delete('/sessions/{id}', [SessionsController::class, 'destroy']);

        // Office settings (admin/head only)
        Route::get('/office',           [OfficeController::class, 'show']);
        Route::put('/office',           [OfficeController::class, 'update']);
        Route::get('/office/members',   [OfficeController::class, 'members']);
        Route::get('/office/defaults',  [OfficeController::class, 'defaults']);
        Route::put('/office/defaults',  [OfficeController::class, 'updateDefaults']);
    });

    // Notification preferences (placed under notifications prefix per CLAUDE.md)
    Route::get('/notifications/preferences',  [PreferencesController::class, 'notificationPreferences']);
    Route::put('/notifications/preferences',  [PreferencesController::class, 'updateNotificationPreferences']);
});

Route::get('/machine-data', function () {
    return response()->json(['message' => 'Client credentials token is valid.']);
})->middleware(EnsureClientIsResourceOwner::class);

Route::get('/user', fn(Request $request) => $request->user())->middleware(EnsureClientIsResourceOwner::class);
Route::post('/login', [AuthController::class, 'login'])->name('login');
