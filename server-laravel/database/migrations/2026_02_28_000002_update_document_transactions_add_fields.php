<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'Returned' to status enum
        DB::statement('ALTER TABLE document_transactions DROP CONSTRAINT IF EXISTS document_transactions_status_check');
        DB::statement("ALTER TABLE document_transactions ADD CONSTRAINT document_transactions_status_check CHECK (status IN ('Draft', 'Processing', 'Returned', 'Completed'))");

        Schema::table('document_transactions', function (Blueprint $table) {
            // parent_transaction_no already exists in DB — skip
            // Urgency
            $table->enum('urgency_level', ['Urgent', 'High', 'Normal', 'Routine'])->default('High');
            $table->date('due_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('document_transactions', function (Blueprint $table) {
            $table->dropColumn(['urgency_level', 'due_date']);
        });

        DB::statement('ALTER TABLE document_transactions DROP CONSTRAINT IF EXISTS document_transactions_status_check');
        DB::statement("UPDATE document_transactions SET status = 'Completed' WHERE status = 'Returned'");
        DB::statement("ALTER TABLE document_transactions ADD CONSTRAINT document_transactions_status_check CHECK (status IN ('Draft', 'Processing', 'Completed'))");
    }
};
