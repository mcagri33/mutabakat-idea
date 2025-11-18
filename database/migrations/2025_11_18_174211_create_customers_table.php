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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->unique();

            $table->string('uuid')->nullable();

            $table->string('name');      // Firma adı
            $table->string('email')->nullable();
            $table->string('company')->nullable();
            $table->string('phone')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
