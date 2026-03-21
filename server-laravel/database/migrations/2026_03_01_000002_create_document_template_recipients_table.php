<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_template_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')
                ->constrained('document_templates')
                ->onDelete('cascade');
            $table->string('office_id');
            $table->string('office_name', 150);
            $table->enum('recipient_type', ['default', 'cc', 'bcc'])->default('default');
            $table->integer('sequence')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_template_recipients');
    }
};
