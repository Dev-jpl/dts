<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        DB::statement('ALTER TABLE office_files ADD COLUMN embedding vector(384)');

        DB::statement('
            CREATE INDEX office_files_embedding_idx ON office_files
            USING hnsw (embedding vector_cosine_ops)
            WITH (m = 16, ef_construction = 64)
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS office_files_embedding_idx');
        DB::statement('ALTER TABLE office_files DROP COLUMN IF EXISTS embedding');
    }
};
