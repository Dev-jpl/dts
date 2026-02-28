<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();

            // Document reference
            $table->string('document_no', 50);
            $table->foreign('document_no')
                ->references('document_no')
                ->on('documents')
                ->cascadeOnDelete();

            // Transaction context (which release/re-release created this version)
            $table->string('transaction_no', 50);
            $table->foreign('transaction_no')
                ->references('transaction_no')
                ->on('document_transactions')
                ->cascadeOnDelete();

            // Version counter (1-based, incremented on each re-release)
            $table->unsignedInteger('version_number');

            // Snapshot of document fields at the time of this version
            $table->string('subject', 255);
            $table->string('action_type', 50);
            $table->string('document_type', 50);
            $table->string('origin_type', 50);
            $table->string('remarks', 255)->nullable();

            // Full recipients snapshot at release time
            $table->json('recipients_snapshot');

            // Who made the change
            $table->foreignUuid('changed_by_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->string('changed_by_name', 150);
            $table->timestamp('changed_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
