<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_reconciliation_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('bank_name');
            $table->string('branch_name')->nullable();
            $table->unsignedSmallInteger('year');
            $table->date('requested_at')->nullable()->comment('Firma bankaya talebi ne zaman yaptı');
            $table->date('reply_received_at')->nullable()->comment('Banka dönüşü ne zaman geldi');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->index(['customer_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_reconciliation_entries');
    }
};
