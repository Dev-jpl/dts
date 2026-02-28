<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_library', function (Blueprint $table) {
            $table->id();

            // Action name — used as action_type value in documents/transactions
            $table->string('name', 100)->unique();

            // FA = For Action (recipient must take terminal action)
            // FI = For Information (Receive is the terminal action)
            $table->enum('type', ['FA', 'FI'])->default('FA');

            // If true, a Reply log marks the recipient's obligation as fulfilled
            $table->boolean('reply_is_terminal')->default(false);

            // If true, Mark as Done requires a proof attachment
            $table->boolean('requires_proof')->default(false);
            $table->string('proof_description')->nullable();

            // Overrides the transaction-level urgency_level when set
            $table->enum('default_urgency_level', ['Urgent', 'High', 'Normal', 'Routine'])->nullable();

            $table->boolean('isActive')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_library');
    }
};
