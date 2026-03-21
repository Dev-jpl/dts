<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PostgreSQL GIN indexes for full-text search
     */
    public function up(): void
    {
        // Add full-text search index on documents.subject
        DB::statement("
            CREATE INDEX IF NOT EXISTS documents_subject_fulltext_idx 
            ON documents 
            USING GIN (to_tsvector('english', subject))
        ");

        // Add full-text search index on document_notes.note
        DB::statement("
            CREATE INDEX IF NOT EXISTS document_notes_note_fulltext_idx 
            ON document_notes 
            USING GIN (to_tsvector('english', note))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS documents_subject_fulltext_idx");
        DB::statement("DROP INDEX IF EXISTS document_notes_note_fulltext_idx");
    }
};
