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
        Schema::create('document_transactions', function (Blueprint $table) {
            // Primary Key
            $table->string('transaction_no')->primary();

            // Transaction Information
            $table->enum('transaction_type', ['Default', 'Forward', 'Reply'])->default('Default');
            $table->enum('routing', ['Single', 'Multiple', 'Sequential'])->default('Single');

            // Document Reference
            $table->string('document_no', 50);
            $table->foreign('document_no')
                ->references('document_no')
                ->on('documents')
                ->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->string('action_type', 50);
            $table->string('origin_type', 50);

            // Content
            $table->string('subject', 255);
            $table->string('remarks', 255)->nullable();

            // Status
            $table->enum('status', ['Draft', 'Processing', 'Completed'])->default('Draft');

            // Office Information
            $table->string('office_id', 50);
            $table->foreign('office_id')
                ->references('id')
                ->on('office_libraries')
                ->onDelete('cascade');
            $table->string('office_name', 150);

            // Creator Information
            $table->foreignUuid('created_by_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->string('created_by_name', 150);

            // Active Flag
            $table->boolean('isActive')->default(true);

            // Timestamps
            $table->timestamps(); // includes created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_transactions');
    }
};
