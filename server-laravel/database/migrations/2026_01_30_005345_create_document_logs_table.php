<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_logs', function (Blueprint $table) {
            // Primary Key
            $table->id(); // auto-increment integer

            // References
            $table->string('document_no', 50);
            $table->string('transaction_no', 50);

            $table->foreign('document_no')
                ->references('document_no')
                ->on('documents')
                ->cascadeOnDelete();

            $table->foreign('transaction_no')
                ->references('transaction_no')
                ->on('document_transactions')
                ->cascadeOnDelete();

            // Office Information
            $table->string('office_id', 50);
            $table->foreign('office_id')
                ->references('id')
                ->on('office_libraries')
                ->onDelete('cascade');
            $table->string('office_name', 150);

            // Status
            $table->enum('status', [
                'Profiled',
                'Received',
                'Released',
                'Archived',
                'Returned To Sender',
                'Forwarded'
            ])->default('Profiled');

            // Action and Activity
            $table->string('action_taken', 100);
            $table->string('activity', 150);
            $table->string('remarks', 255)->nullable();

            // Assigned Personnel
            // $table->string('assigned_personnel_id', 50);
            $table->foreignUuid('assigned_personnel_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            // $table->string('created_by_name', 150);
            $table->string('assigned_personnel_name', 150);

            // Creator Information



            // Timestamps
            $table->timestamps(); // includes created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_logs');
    }
};
