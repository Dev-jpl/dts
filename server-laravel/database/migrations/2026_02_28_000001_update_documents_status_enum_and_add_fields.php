<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Drop old check constraint
        DB::statement('ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_status_check');

        // Step 2: Migrate existing data to new values
        DB::statement("UPDATE documents SET status = 'Active' WHERE status = 'Processing'");
        DB::statement("UPDATE documents SET status = 'Closed' WHERE status = 'Archived'");

        // Step 3: Add new check constraint
        DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_status_check CHECK (status IN ('Draft', 'Active', 'Returned', 'Completed', 'Closed'))");

        // Step 4: Add new fields
        Schema::table('documents', function (Blueprint $table) {
            $table->boolean('allow_copy')->default(false);
            $table->string('qr_code')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['allow_copy', 'qr_code']);
        });

        DB::statement('ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_status_check');
        DB::statement("UPDATE documents SET status = 'Processing' WHERE status = 'Active'");
        DB::statement("UPDATE documents SET status = 'Archived' WHERE status = 'Closed'");
        DB::statement("UPDATE documents SET status = 'Processing' WHERE status IN ('Returned', 'Completed')");
        DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_status_check CHECK (status IN ('Draft', 'Processing', 'Archived'))");
    }
};
