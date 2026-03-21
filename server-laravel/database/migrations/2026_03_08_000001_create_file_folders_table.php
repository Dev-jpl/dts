<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_folders', function (Blueprint $table) {
            $table->id();
            $table->string('office_id', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->uuid('created_by_id');
            $table->string('created_by_name', 150);
            $table->timestamps();

            $table->foreign('office_id')->references('id')->on('office_libraries')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('file_folders')->onDelete('cascade');
            $table->foreign('created_by_id')->references('id')->on('users');

            $table->unique(['office_id', 'name', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_folders');
    }
};
