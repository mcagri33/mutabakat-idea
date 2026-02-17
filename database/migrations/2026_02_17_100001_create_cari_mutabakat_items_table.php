<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cari_mutabakat_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->string('hesap_tipi')->nullable();
            $table->string('referans')->nullable();
            $table->string('cari_kodu', 10); // 120 veya 320
            $table->string('unvan');
            $table->string('email');
            $table->string('cc_email')->nullable();
            $table->string('tel_no')->nullable();
            $table->string('vergi_no')->nullable();
            $table->date('tarih');
            $table->enum('bakiye_tipi', ['Borç', 'Alacak'])->default('Borç');
            $table->decimal('bakiye', 18, 2)->default(0);
            $table->string('pb', 10)->nullable(); // TL, USD, EUR
            $table->decimal('karsiligi', 18, 2)->nullable();
            $table->string('token', 64)->unique()->nullable();
            $table->enum('reply_status', ['pending', 'received', 'completed'])->default('pending');
            $table->timestamp('reply_received_at')->nullable();
            $table->timestamps();

            $table->foreign('request_id')->references('id')->on('cari_mutabakat_requests')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cari_mutabakat_items');
    }
};
