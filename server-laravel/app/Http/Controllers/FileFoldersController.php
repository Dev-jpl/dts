<?php

namespace App\Http\Controllers;

use App\Models\FileFolder;
use App\Models\OfficeFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileFoldersController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // List folders with file counts
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $folders = FileFolder::forOffice($user->office_id)
            ->withCount('files')
            ->with(['children' => function ($q) {
                $q->withCount('files')->orderBy('name');
            }])
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        // Also get count of root-level files (no folder)
        $rootFileCount = OfficeFile::forOffice($user->office_id)
            ->whereNull('folder_id')
            ->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'folders'         => $folders,
                'root_file_count' => $rootFileCount,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Create folder
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'parent_id'   => 'nullable|integer|exists:file_folders,id',
        ]);

        $user = $request->user();

        // Verify parent folder belongs to this office
        if ($request->filled('parent_id')) {
            $parent = FileFolder::forOffice($user->office_id)->find($request->input('parent_id'));
            if (!$parent) {
                return response()->json(['success' => false, 'message' => 'Parent folder not found.'], 404);
            }
        }

        // Check for duplicate name within same parent + office
        $exists = FileFolder::forOffice($user->office_id)
            ->where('name', $request->input('name'))
            ->where('parent_id', $request->input('parent_id'))
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A folder with this name already exists in this location.',
            ], 422);
        }

        $folder = FileFolder::create([
            'office_id'       => $user->office_id,
            'name'            => $request->input('name'),
            'description'     => $request->input('description'),
            'parent_id'       => $request->input('parent_id'),
            'created_by_id'   => $user->id,
            'created_by_name' => $user->fullName(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Folder created successfully.',
            'data'    => $folder,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Update (rename/description)
    // ─────────────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);

        $user   = $request->user();
        $folder = FileFolder::forOffice($user->office_id)->find($id);

        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Folder not found.'], 404);
        }

        // Check for duplicate name within same parent + office (excluding self)
        $exists = FileFolder::forOffice($user->office_id)
            ->where('name', $request->input('name'))
            ->where('parent_id', $folder->parent_id)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A folder with this name already exists in this location.',
            ], 422);
        }

        $folder->update([
            'name'        => $request->input('name'),
            'description' => $request->input('description'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Folder updated successfully.',
            'data'    => $folder->refresh(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Delete (guard: no files inside)
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user   = $request->user();
        $folder = FileFolder::forOffice($user->office_id)->find($id);

        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Folder not found.'], 404);
        }

        // Guard: cannot delete if folder has files
        $fileCount = OfficeFile::where('folder_id', $id)->count();
        if ($fileCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: folder contains {$fileCount} file(s). Move or delete them first.",
            ], 422);
        }

        // Guard: cannot delete if folder has sub-folders
        $childCount = FileFolder::where('parent_id', $id)->count();
        if ($childCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: folder contains {$childCount} sub-folder(s).",
            ], 422);
        }

        $folder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Folder deleted successfully.',
        ]);
    }
}
