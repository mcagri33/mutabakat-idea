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
        // reconciliation_requests tablosuna indeksler
        Schema::table('reconciliation_requests', function (Blueprint $table) {
            $table->index('created_at', 'idx_reconciliation_requests_created_at');
            $table->index('year', 'idx_reconciliation_requests_year');
            $table->index('status', 'idx_reconciliation_requests_status');
            $table->index('customer_id', 'idx_reconciliation_requests_customer_id');
        });
        
        // reconciliation_banks tablosuna indeksler
        Schema::table('reconciliation_banks', function (Blueprint $table) {
            $table->index('request_id', 'idx_reconciliation_banks_request_id');
            $table->index('reply_status', 'idx_reconciliation_banks_reply_status');
            $table->index('mail_status', 'idx_reconciliation_banks_mail_status');
            $table->index('customer_id', 'idx_reconciliation_banks_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reconciliation_requests', function (Blueprint $table) {
            $table->dropIndex('idx_reconciliation_requests_created_at');
            $table->dropIndex('idx_reconciliation_requests_year');
            $table->dropIndex('idx_reconciliation_requests_status');
            $table->dropIndex('idx_reconciliation_requests_customer_id');
        });
        
        Schema::table('reconciliation_banks', function (Blueprint $table) {
            $table->dropIndex('idx_reconciliation_banks_request_id');
            $table->dropIndex('idx_reconciliation_banks_reply_status');
            $table->dropIndex('idx_reconciliation_banks_mail_status');
            $table->dropIndex('idx_reconciliation_banks_customer_id');
        });
    }
};
