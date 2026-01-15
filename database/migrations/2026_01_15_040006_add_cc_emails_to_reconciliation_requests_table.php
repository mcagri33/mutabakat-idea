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
        Schema::table('reconciliation_requests', function (Blueprint $table) {
            $table->text('cc_emails')->nullable()->after('notes');
            $table->boolean('include_auto_cc')->default(true)->after('cc_emails');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reconciliation_requests', function (Blueprint $table) {
            $table->dropColumn(['cc_emails', 'include_auto_cc']);
        });
    }
};
