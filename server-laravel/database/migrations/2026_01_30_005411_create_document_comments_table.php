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
        Schema::create('document_comments', function (Blueprint $table) {
            // Primary Key
            $table->id(); // auto-increment integer

            // References
            $table->string('document_no', 50);
            $table->string('transaction_no', 50); // corrected spelling

            // Office Information
            $table->string('office_id', 50);
            $table->foreign('office_id')
                ->references('id')
                ->on('office_libraries')
                ->onDelete('cascade');
            $table->string('office_name', 150);

            // Comment
            $table->string('comment', 500);

            // Assigned Personnel
            $table->foreignUuid('assigned_personnel_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->string('assigned_personnel_name', 150);



            // Timestamps
            $table->timestamps(); // includes created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_comments');
    }
};
