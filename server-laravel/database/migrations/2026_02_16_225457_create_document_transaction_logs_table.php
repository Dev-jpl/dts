<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_transaction_logs', function (Blueprint $table) {
            $table->id();
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

            $table->string('routed_office_id', 50)->nullable();
            // $table->foreign('routed_office_id')->references('id')->on('office_libraries')->onDelete('set null');
            $table->string('routed_office_name', 150)->nullable();

            // Status
            $table->enum('status', [
                'Profiled',
                'Received',
                'Released',
                'Archived',
                'Returned To Sender',
                'Forwarded'
            ]);

            // Action and Activity
            $table->string('action_taken', 150)->nullable();
            $table->mediumText('activity');
            $table->string('remarks', 255)->nullable();


            // Assigned Personnel
            $table->foreignUuid('assigned_personnel_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->string('assigned_personnel_name', 150);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_transaction_logs');
    }
};
