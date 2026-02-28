<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expand status enum with all new values (keep Archived for backward compat)
        DB::statement('ALTER TABLE document_transaction_logs DROP CONSTRAINT IF EXISTS document_transaction_logs_status_check');
        DB::statement("ALTER TABLE document_transaction_logs ADD CONSTRAINT document_transaction_logs_status_check CHECK (status IN (
            'Profiled', 'Released', 'Received', 'Forwarded', 'Returned To Sender', 'Archived',
            'Done', 'Closed', 'Routing Halted', 'Document Revised',
            'Recipient Added', 'Recipient Removed', 'Recipients Reordered'
        ))");

        Schema::table('document_transaction_logs', function (Blueprint $table) {
            // Required on Return to Sender; optional for other actions
            $table->string('reason')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('document_transaction_logs', function (Blueprint $table) {
            $table->dropColumn('reason');
        });

        DB::statement('ALTER TABLE document_transaction_logs DROP CONSTRAINT IF EXISTS document_transaction_logs_status_check');
        DB::statement("ALTER TABLE document_transaction_logs ADD CONSTRAINT document_transaction_logs_status_check CHECK (status IN (
            'Profiled', 'Received', 'Released', 'Archived', 'Returned To Sender', 'Forwarded'
        ))");
    }
};
