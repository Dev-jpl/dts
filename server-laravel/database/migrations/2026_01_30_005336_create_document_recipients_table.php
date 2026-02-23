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
        Schema::create('document_recipients', function (Blueprint $table) {
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

            // Recipient Type
            $table->enum('recipient_type', ['default', 'cc', 'bcc'])->default('default');

            //
            $table->integer('sequence')->nullable()->unsigned();

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
        Schema::dropIfExists('document_recipients');
    }
};
