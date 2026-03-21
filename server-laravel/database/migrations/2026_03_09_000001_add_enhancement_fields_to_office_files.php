<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_files', function (Blueprint $table) {
            $table->string('enhanced_path', 500)->nullable()->after('file_size');
            $table->string('thumbnail_path', 500)->nullable()->after('enhanced_path');
            $table->string('enhancement_status', 20)->default('pending')->after('thumbnail_path');
            $table->text('enhancement_error')->nullable()->after('enhancement_status');
            $table->string('searchable_pdf_path', 500)->nullable()->after('enhancement_error');
        });

        // Existing rows: mark as skipped so they don't appear stuck
        DB::table('office_files')->whereNull('enhancement_status')->update([
            'enhancement_status' => 'skipped',
        ]);
    }

    public function down(): void
    {
        Schema::table('office_files', function (Blueprint $table) {
            $table->dropColumn([
                'enhanced_path',
                'thumbnail_path',
                'enhancement_status',
                'enhancement_error',
                'searchable_pdf_path',
            ]);
        });
    }
};
