<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cari_mutabakat_items', function (Blueprint $table) {
            $table->enum('mail_status', ['pending', 'sent', 'failed'])->default('pending')->after('reply_received_at');
        });
    }

    public function down(): void
    {
        Schema::table('cari_mutabakat_items', function (Blueprint $table) {
            $table->dropColumn('mail_status');
        });
    }
};
