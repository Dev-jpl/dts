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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->unique();
            $table->string('date_format', 20)->default('Y-m-d');
            $table->string('timezone', 50)->default('Asia/Manila');
            $table->enum('default_period', ['week', 'month', 'quarter', 'year'])->default('month');
            $table->boolean('dashboard_realtime')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
