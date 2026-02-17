<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cari_mutabakat_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->year('year');
            $table->unsignedTinyInteger('month')->nullable();
            $table->enum('status', [
                'pending',
                'sent',
                'partially_received',
                'completed',
                'failed',
            ])->default('pending');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cari_mutabakat_requests');
    }
};
