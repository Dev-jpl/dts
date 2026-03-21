<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->string('office_id', 50);
            $table->string('file_name', 255);
            $table->string('original_name', 255);
            $table->string('file_path', 500);
            $table->string('mime_type', 100);
            $table->bigInteger('file_size');
            $table->text('ocr_text')->nullable();
            $table->string('ocr_status', 20)->default('pending');
            $table->text('ocr_error')->nullable();
            $table->uuid('uploaded_by_id');
            $table->string('uploaded_by_name', 150);
            $table->timestamps();

            $table->foreign('folder_id')->references('id')->on('file_folders')->onDelete('set null');
            $table->foreign('office_id')->references('id')->on('office_libraries')->onDelete('cascade');
            $table->foreign('uploaded_by_id')->references('id')->on('users');

            $table->index('office_id');
            $table->index('ocr_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_files');
    }
};
