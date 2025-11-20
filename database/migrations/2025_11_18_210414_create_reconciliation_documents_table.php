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
        Schema::create('reconciliation_documents', function (Blueprint $table) {
            $table->id();

            // Hangi bankaya ait belge
            $table->unsignedBigInteger('bank_id');

            // Dosya bilgisi
            $table->string('file_path');       // storage/app/...
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable(); // pdf, xlsx, jpg...

            // Kim yükledi?
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('uploaded_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('bank_id')
                ->references('id')->on('reconciliation_banks')
                ->onDelete('cascade');

            $table->foreign('uploaded_by')
                ->references('id')->on('users') // Filament admin users tablosu
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_documents');
    }
};
