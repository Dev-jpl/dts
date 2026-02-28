<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_notes', function (Blueprint $table) {
            $table->id();

            // Scope: entire document (not a single transaction)
            $table->string('document_no', 50);
            $table->foreign('document_no')
                ->references('document_no')
                ->on('documents')
                ->cascadeOnDelete();

            // Context: which transaction the note was added from
            $table->string('transaction_no', 50);
            $table->foreign('transaction_no')
                ->references('transaction_no')
                ->on('document_transactions')
                ->cascadeOnDelete();

            // Note content
            $table->text('note');

            // Author office
            $table->string('office_id', 50);
            $table->foreign('office_id')
                ->references('id')
                ->on('office_libraries')
                ->onDelete('cascade');
            $table->string('office_name', 150);

            // Author user
            $table->foreignUuid('created_by_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->string('created_by_name', 150);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_notes');
    }
};
