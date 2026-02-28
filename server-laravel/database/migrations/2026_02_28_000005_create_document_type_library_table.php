<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_type_library', function (Blueprint $table) {
            $table->id();

            // Type name — used as document_type value in documents/transactions
            $table->string('name', 100)->unique();

            // When set, overrides the system default urgency (High) for this document type
            $table->enum('default_urgency_level', ['Urgent', 'High', 'Normal', 'Routine'])->nullable();

            $table->boolean('isActive')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_type_library');
    }
};
