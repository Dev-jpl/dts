<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->string('sender', 150)->nullable()->after('origin_type');
            $table->string('sender_position', 150)->nullable()->after('sender');
            $table->string('sender_office', 150)->nullable()->after('sender_position');
            $table->string('sender_email', 255)->nullable()->after('sender_office');
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn(['sender', 'sender_position', 'sender_office', 'sender_email']);
        });
    }
};
