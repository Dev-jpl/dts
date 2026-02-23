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
        Schema::create('documents', function (Blueprint $table) {
            // Primary Key
            $table->string('document_no')->primary();

            // Document Information
            $table->string('document_type', 50);
            $table->string('action_type', 50);
            $table->string('origin_type', 50);
            $table->string('subject', 255);
            $table->string('remarks', 255)->nullable();

            // Status (enum equivalent in Laravel)
            $table->enum('status', ['Draft', 'Processing', 'Archived'])->default('Draft');

            // Office Information
            $table->string('office_id', 50);
            $table->string('office_name', 150);

            // Creator Information
            $table->uuid('created_by_id', 50);
            $table->foreignUuid('created_by_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->string('created_by_name', 150);

            // Status
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
        Schema::dropIfExists('documents');
    }
};
