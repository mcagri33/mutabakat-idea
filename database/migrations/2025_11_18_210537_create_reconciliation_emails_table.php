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
        Schema::create('reconciliation_emails', function (Blueprint $table) {
            $table->id();

            // Hangi mutabakat talebine ait?
            $table->unsignedBigInteger('request_id');

            // Hangi bankaya gönderildi?
            $table->unsignedBigInteger('bank_id')->nullable();

            // Gönderilen kişi
            $table->string('sent_to');   // officer_email

            // Mail metni
            $table->string('subject')->nullable();
            $table->text('body')->nullable();

            // Durum
            $table->enum('status', ['sent', 'failed', 'bounced'])
                  ->default('sent');

            // Gönderim zamanı
            $table->timestamp('sent_at')->nullable();

            // Hata mesajı
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('request_id')
                ->references('id')->on('reconciliation_requests')
                ->onDelete('cascade');

            $table->foreign('bank_id')
                ->references('id')->on('reconciliation_banks')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_emails');
    }
};
