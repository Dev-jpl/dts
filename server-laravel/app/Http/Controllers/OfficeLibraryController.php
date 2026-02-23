<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class OfficeLibraryController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            // Query directly from the SQL view
            $offices = DB::table('v_office_libraries')->where('isactive', true)->get();

            // dd($office);

            $mapped = $offices->map(function ($office) {
                return [
                    'item_id'    => $office->id, // unique id from view
                    'item_title' => $office->office_name,
                    'item_desc'  => "{$office->region_name} - {$office->office_type}",
                    'return_value' => [
                        'region_code'          => $office->region_code,
                        'region_name'          => $office->region_name,
                        'province_code'        => $office->province_code,
                        'province_name'        => $office->province_name,
                        'municipality_code'    => $office->municipality_code,
                        'municipality_name'    => $office->municipality_name,
                        'office'               => $office->office_name,
                        'office_code'          => $office->id,
                        'office_type'          => $office->office_type,
                        'superior_office_id'   => $office->superior_office_id,
                        'superior_office_name' => $office->superior_office_name,
                        // extra parent info from the view
                        'parent_office_name'   => $office->parent_office_name,
                        'parent_office_type'   => $office->parent_office_type,
                    ],
                ];
            });

            return response()->json($mapped, 200);
        } catch (\Throwable $th) {
            \Log::error('OfficeLibraryController@index failed', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch office library data.',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
