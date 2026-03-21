<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('office_file_id')->nullable()->after('office_name');
            $table->foreign('office_file_id')->references('id')->on('office_files')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('document_attachments', function (Blueprint $table) {
            $table->dropForeign(['office_file_id']);
            $table->dropColumn('office_file_id');
        });
    }
};
