<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_template_signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')
                ->constrained('document_templates')
                ->onDelete('cascade');
            $table->string('signatory_id')->nullable(); // Reference to signatory_library
            $table->string('name', 150);
            $table->string('position', 150)->nullable();
            $table->string('office', 150)->nullable();
            $table->enum('role', ['Noted', 'Approved', 'Signed', 'Certified'])->default('Signed');
            $table->integer('sequence')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_template_signatories');
    }
};
