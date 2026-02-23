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
        Schema::create('office_libraries', function (Blueprint $table) {
            // Primary Key
            $table->string('id', 50)->primary();

            // Office Information
            $table->string('office_name', 150);

            // Region / Province / Municipality
            $table->string('region_code', 50)->nullable();
            $table->string('region_name', 150)->nullable();
            $table->string('province_code', 50)->nullable();
            $table->string('province_name', 150)->nullable();
            $table->string('municipality_code', 50)->nullable();
            $table->string('municipality_name', 150)->nullable();

            // Office Type
            $table->enum('office_type', ['Agency', 'Service', 'Division', 'Section', 'Unit']);

            // Superior Office Reference
            $table->string('superior_office_id', 50)->nullable();
            $table->string('superior_office_name', 150)->nullable();

            // Active Flag
            $table->boolean('isActive')->default(true);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('office_name');
            $table->index('region_code');
            $table->index('province_code');
            $table->index('municipality_code');
            $table->index('office_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_library');
    }
};
