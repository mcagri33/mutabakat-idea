<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cari_mutabakat_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->enum('cevap', ['mutabıkız', 'mutabık_değiliz']);
            $table->string('cevaplayan_unvan')->nullable();
            $table->string('cevaplayan_vergi_no')->nullable();
            $table->text('aciklama')->nullable();
            $table->string('ekstre_path')->nullable();
            $table->string('e_imzali_form_path')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('cari_mutabakat_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cari_mutabakat_replies');
    }
};
