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
        Schema::create('reconciliation_incoming_emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('bank_id')->nullable();
            
            // Gelen mail bilgileri
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('subject');
            $table->text('body')->nullable();
            $table->text('html_body')->nullable();
            
            // Mail header bilgileri
            $table->string('message_id')->unique();
            $table->timestamp('received_at');
            
            // Eşleştirme durumu
            $table->enum('match_status', ['pending', 'matched', 'unmatched'])->default('pending');
            $table->text('match_notes')->nullable();
            
            // İşlem durumu
            $table->enum('status', ['new', 'processed', 'archived'])->default('new');
            
            // Ekler (attachments) - JSON formatında
            $table->json('attachments')->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('request_id')
                ->references('id')
                ->on('reconciliation_requests')
                ->onDelete('cascade');
                
            $table->foreign('bank_id')
                ->references('id')
                ->on('reconciliation_banks')
                ->onDelete('set null');
            
            // Indexes
            $table->index('from_email');
            $table->index('match_status');
            $table->index('status');
            $table->index('received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_incoming_emails');
    }
};
