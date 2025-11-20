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
        Schema::create('reconciliation_requests', function (Blueprint $table) {
             $table->id();

            // Firma
            $table->unsignedBigInteger('customer_id');

            // Mutabakat tipi: banka / cari
            $table->enum('type', ['banka', 'cari'])->default('banka');

            // Yıl seçimi
            $table->year('year');

            // Aylık mutabakat gerekirse (cari için kullanılabilir)
            $table->unsignedTinyInteger('month')->nullable();

            // Genel durum
            $table->enum('status', [
                'pending',       // Oluşturuldu
                'mail_sent',     // Tüm bankalara mail gönderildi
                'partially',     // Bazı bankalardan yanıt geldi
                'received',      // Tüm bankalardan yanıt geldi
                'completed',     // Admin tarafından tamamen onaylandı
                'failed',        // Mail sorunları vb.
            ])->default('pending');

            // Tarihler
            $table->timestamp('requested_at')->nullable();   // admin oluşturma zamanı
            $table->timestamp('sent_at')->nullable();        // maillerin gönderildiği an
            $table->timestamp('received_at')->nullable();    // tüm bankalardan belge geldiği an

            $table->text('notes')->nullable();

            $table->timestamps();

            // Foreign key
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('customers')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_requests');
    }
};
