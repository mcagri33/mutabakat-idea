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
        Schema::create('reconciliation_banks', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('request_id'); 
                $table->unsignedBigInteger('customer_id');        // 🔥 direkt burada
        // bağlandığı mutabakat talebi
            $table->unsignedBigInteger('customer_bank_id')->nullable(); // firmaya tanımlı banka kaynağı

            // Dinamik olarak talep içine kopyalanan alanlar
            $table->string('bank_name');
            $table->string('branch_name')->nullable();
            $table->string('officer_name')->nullable();
            $table->string('officer_email');
            $table->string('officer_phone')->nullable();

            // Mail gönderim durumu
            $table->enum('mail_status', [
                'pending',       // henüz gönderilmedi
                'sent',          // mail gönderildi
                'failed',        // hata oluştu
            ])->default('pending');

            // Bankadan dönüş durumu
            $table->enum('reply_status', [
                'pending',       // bekliyor
                'received',      // belge geldi
                'approved',      // admin onayladı
            ])->default('pending');

            // Tarihler
            $table->timestamp('mail_sent_at')->nullable();
            $table->timestamp('reply_received_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('request_id')
                ->references('id')->on('reconciliation_requests')
                ->onDelete('cascade');

                    $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');

            $table->foreign('customer_bank_id')
                ->references('id')->on('customer_banks')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_banks');
    }
};
