<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->enum('scope', ['personal', 'office', 'system'])->default('personal');
            $table->string('document_type', 100)->nullable();
            $table->string('action_type', 100)->nullable();
            $table->enum('routing_type', ['Single', 'Multiple', 'Sequential'])->nullable();
            $table->enum('urgency_level', ['Urgent', 'High', 'Normal', 'Routine'])->default('High');
            $table->string('origin_type', 100)->nullable();
            $table->text('remarks_template')->nullable();
            $table->uuid('created_by_id');
            $table->string('created_by_name', 150);
            $table->string('office_id');
            $table->boolean('isActive')->default(true);
            $table->integer('use_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('office_id')->references('id')->on('office_libraries')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
