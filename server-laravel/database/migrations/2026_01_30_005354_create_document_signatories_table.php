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
        Schema::create('document_signatories', function (Blueprint $table) {
            // Primary Key
            $table->bigIncrements('id')->primary(); // varchar as specified

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


            // Employee Information
            $table->uuid('employee_id', 50);
            $table->string('employee_name', 150);

            // Timestamps
            $table->timestamps(); // includes created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_signatories');
    }
};
