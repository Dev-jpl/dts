<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE INDEX office_files_ocr_text_gin ON office_files USING gin(to_tsvector('simple', COALESCE(ocr_text, '')))");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS office_files_ocr_text_gin");
    }
};
