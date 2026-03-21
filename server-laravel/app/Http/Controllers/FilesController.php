<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessFileJob;
use App\Models\DocumentAttachment;
use App\Models\FileFolder;
use App\Models\OfficeFile;
use App\Services\EmbeddingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FilesController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // List files (paginated, filterable, searchable — hybrid keyword+semantic)
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = OfficeFile::forOffice($user->office_id)
            ->with('folder:id,name');

        // Folder filter
        if ($request->filled('folder_id')) {
            if ($request->input('include_subfolders') === 'true') {
                $parentId = (int) $request->input('folder_id');
                $childIds = FileFolder::where('parent_id', $parentId)->pluck('id')->toArray();
                $query->whereIn('folder_id', array_merge([$parentId], $childIds));
            } else {
                $query->where('folder_id', $request->input('folder_id'));
            }
        } elseif ($request->input('folder_id') === '0' || $request->input('root_only') === 'true') {
            $query->whereNull('folder_id');
        }

        // Search (hybrid: keyword + semantic)
        $searchMode = $request->input('search_mode', 'smart');
        $actualMode = 'keyword'; // default unless smart search succeeds

        if ($request->filled('search')) {
            $keyword = $request->input('search');

            if ($searchMode === 'smart') {
                $vector = EmbeddingService::embed($keyword);
                if ($vector) {
                    $query->hybridSearch($keyword, $vector);
                    $actualMode = 'smart';
                } else {
                    $query->keywordSearch($keyword);
                }
            } else {
                $query->keywordSearch($keyword);
            }
        }

        // Mime type filter
        if ($request->filled('mime_type')) {
            $query->where('mime_type', $request->input('mime_type'));
        }

        // OCR status filter
        if ($request->filled('ocr_status')) {
            $query->where('ocr_status', $request->input('ocr_status'));
        }

        // Date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        // Sort (only when not doing hybrid search — hybrid has its own ranking)
        if ($actualMode !== 'smart') {
            $sortBy  = $request->input('sort_by', 'created_at');
            $sortDir = $request->input('sort_dir', 'desc');
            $allowedSorts = ['created_at', 'original_name', 'file_size', 'ocr_status'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
            }
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        return response()->json([
            'success'     => true,
            'search_mode' => $request->filled('search') ? $actualMode : null,
            'data'        => $query->paginate($perPage),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Show single file detail
    // ─────────────────────────────────────────────────────────────────────────

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $file = OfficeFile::forOffice($user->office_id)
            ->with('folder:id,name')
            ->find($id);

        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        $attachmentCount = DocumentAttachment::where('office_file_id', $id)->count();

        return response()->json([
            'success' => true,
            'data'    => array_merge($file->toArray(), [
                'attachment_count'   => $attachmentCount,
                'has_enhanced'       => !empty($file->enhanced_path),
                'has_thumbnail'      => !empty($file->thumbnail_path),
                'has_searchable_pdf' => !empty($file->searchable_pdf_path),
            ]),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Upload single file
    // ─────────────────────────────────────────────────────────────────────────

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file'      => 'required|file|mimes:jpeg,jpg,png,pdf',
            'folder_id' => 'nullable|integer|exists:file_folders,id',
        ]);

        $user       = $request->user();
        $uploadFile = $request->file('file');

        if ($request->filled('folder_id')) {
            $folder = FileFolder::forOffice($user->office_id)->find($request->input('folder_id'));
            if (!$folder) {
                return response()->json(['success' => false, 'message' => 'Folder not found.'], 404);
            }
        }

        $uuid         = Str::uuid()->toString();
        $extension    = $uploadFile->getClientOriginalExtension();
        $storedName   = "{$uuid}.{$extension}";
        $folderSegment = $request->input('folder_id', 'root');
        $storagePath  = "files/{$user->office_id}/{$folderSegment}/{$storedName}";

        Storage::disk('local')->put($storagePath, file_get_contents($uploadFile->getRealPath()));

        $officeFile = OfficeFile::create([
            'folder_id'          => $request->input('folder_id'),
            'office_id'          => $user->office_id,
            'file_name'          => $storedName,
            'original_name'      => $uploadFile->getClientOriginalName(),
            'file_path'          => $storagePath,
            'mime_type'          => $uploadFile->getMimeType(),
            'file_size'          => $uploadFile->getSize(),
            'enhancement_status' => OfficeFile::STATUS_PENDING,
            'ocr_status'         => OfficeFile::STATUS_PENDING,
            'uploaded_by_id'     => $user->id,
            'uploaded_by_name'   => $user->fullName(),
        ]);

        ProcessFileJob::dispatch($officeFile->id);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully. Processing will begin shortly.',
            'data'    => $officeFile->load('folder:id,name'),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Bulk upload
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkUpload(Request $request): JsonResponse
    {
        $request->validate([
            'files'     => 'required|array|min:1|max:20',
            'files.*'   => 'file|mimes:jpeg,jpg,png,pdf',
            'folder_id' => 'nullable|integer|exists:file_folders,id',
        ]);

        $user = $request->user();

        if ($request->filled('folder_id')) {
            $folder = FileFolder::forOffice($user->office_id)->find($request->input('folder_id'));
            if (!$folder) {
                return response()->json(['success' => false, 'message' => 'Folder not found.'], 404);
            }
        }

        $folderSegment = $request->input('folder_id', 'root');
        $uploaded = [];

        foreach ($request->file('files') as $uploadFile) {
            $uuid       = Str::uuid()->toString();
            $extension  = $uploadFile->getClientOriginalExtension();
            $storedName = "{$uuid}.{$extension}";
            $storagePath = "files/{$user->office_id}/{$folderSegment}/{$storedName}";

            Storage::disk('local')->put($storagePath, file_get_contents($uploadFile->getRealPath()));

            $officeFile = OfficeFile::create([
                'folder_id'          => $request->input('folder_id'),
                'office_id'          => $user->office_id,
                'file_name'          => $storedName,
                'original_name'      => $uploadFile->getClientOriginalName(),
                'file_path'          => $storagePath,
                'mime_type'          => $uploadFile->getMimeType(),
                'file_size'          => $uploadFile->getSize(),
                'enhancement_status' => OfficeFile::STATUS_PENDING,
                'ocr_status'         => OfficeFile::STATUS_PENDING,
                'uploaded_by_id'     => $user->id,
                'uploaded_by_name'   => $user->fullName(),
            ]);

            ProcessFileJob::dispatch($officeFile->id);
            $uploaded[] = $officeFile;
        }

        return response()->json([
            'success' => true,
            'message' => count($uploaded) . ' file(s) uploaded successfully.',
            'data'    => $uploaded,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Preview (inline — enhanced by default, ?version=original for original)
    // ─────────────────────────────────────────────────────────────────────────

    public function preview(Request $request, int $id)
    {
        $user = $request->user();

        $file = OfficeFile::forOffice($user->office_id)->find($id);

        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        // Default to enhanced version, fall back to original
        $version = $request->input('version', 'enhanced');
        if ($version === 'original' || empty($file->enhanced_path)) {
            $filePath = $file->file_path;
        } else {
            $filePath = $file->enhanced_path;
        }

        $absolutePath = Storage::disk('local')->path($filePath);

        if (!file_exists($absolutePath)) {
            return response()->json(['success' => false, 'message' => 'File not found on disk.'], 404);
        }

        return response()->file($absolutePath, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => 'inline; filename="' . $file->original_name . '"',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Thumbnail
    // ─────────────────────────────────────────────────────────────────────────

    public function thumbnail(Request $request, int $id)
    {
        $user = $request->user();

        $file = OfficeFile::forOffice($user->office_id)->find($id);

        if (!$file || empty($file->thumbnail_path)) {
            return response()->json(['success' => false, 'message' => 'Thumbnail not available.'], 404);
        }

        $absolutePath = Storage::disk('local')->path($file->thumbnail_path);

        if (!file_exists($absolutePath)) {
            return response()->json(['success' => false, 'message' => 'Thumbnail not found on disk.'], 404);
        }

        return response()->file($absolutePath, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline; filename="thumb_' . $file->original_name . '.jpg"',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Download searchable PDF (or fall back to original)
    // ─────────────────────────────────────────────────────────────────────────

    public function downloadSearchable(Request $request, int $id)
    {
        $user = $request->user();

        $file = OfficeFile::forOffice($user->office_id)->find($id);

        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        $filePath = $file->searchable_pdf_path ?? $file->file_path;
        $absolutePath = Storage::disk('local')->path($filePath);

        if (!file_exists($absolutePath)) {
            return response()->json(['success' => false, 'message' => 'File not found on disk.'], 404);
        }

        $downloadName = pathinfo($file->original_name, PATHINFO_FILENAME) . '_searchable.pdf';

        return response()->download($absolutePath, $downloadName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Download (authenticated)
    // ─────────────────────────────────────────────────────────────────────────

    public function download(Request $request, int $id)
    {
        $user = $request->user();

        $file = OfficeFile::forOffice($user->office_id)->find($id);

        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        $absolutePath = Storage::disk('local')->path($file->file_path);

        if (!file_exists($absolutePath)) {
            return response()->json(['success' => false, 'message' => 'File not found on disk.'], 404);
        }

        return response()->download($absolutePath, $file->original_name, [
            'Content-Type' => $file->mime_type,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Delete (guard: not attached to transactions)
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $file = OfficeFile::forOffice($user->office_id)->find($id);

        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        $attachmentCount = DocumentAttachment::where('office_file_id', $id)->count();
        if ($attachmentCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: file is attached to {$attachmentCount} transaction(s).",
            ], 422);
        }

        // Delete all associated files from disk
        Storage::disk('local')->delete($file->file_path);
        if ($file->enhanced_path) {
            Storage::disk('local')->delete($file->enhanced_path);
        }
        if ($file->thumbnail_path) {
            Storage::disk('local')->delete($file->thumbnail_path);
        }
        if ($file->searchable_pdf_path) {
            Storage::disk('local')->delete($file->searchable_pdf_path);
        }

        $file->delete();

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reprocess (enhancement + OCR + embedding pipeline)
    // ─────────────────────────────────────────────────────────────────────────

    public function reprocessOcr(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $file = OfficeFile::forOffice($user->office_id)->find($id);

        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        // Clean up old enhanced/thumbnail/searchable files
        if ($file->enhanced_path) {
            Storage::disk('local')->delete($file->enhanced_path);
        }
        if ($file->thumbnail_path) {
            Storage::disk('local')->delete($file->thumbnail_path);
        }
        if ($file->searchable_pdf_path) {
            Storage::disk('local')->delete($file->searchable_pdf_path);
        }

        $file->update([
            'enhancement_status' => OfficeFile::STATUS_PENDING,
            'enhancement_error'  => null,
            'enhanced_path'      => null,
            'thumbnail_path'     => null,
            'searchable_pdf_path' => null,
            'ocr_status'         => OfficeFile::STATUS_PENDING,
            'ocr_text'           => null,
            'ocr_error'          => null,
            'embedding'          => null,
        ]);

        ProcessFileJob::dispatch($file->id);

        return response()->json([
            'success' => true,
            'message' => 'Reprocessing queued.',
            'data'    => $file->refresh(),
        ]);
    }
}
