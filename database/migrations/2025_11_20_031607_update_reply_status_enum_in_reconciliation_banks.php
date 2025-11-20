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
        // MySQL'de enum değiştirmek için raw SQL kullanıyoruz
        // 'approved' değerlerini 'completed' olarak güncelle
        \DB::statement("UPDATE reconciliation_banks SET reply_status = 'completed' WHERE reply_status = 'approved'");
        
        // Enum'u güncelle
        \DB::statement("ALTER TABLE reconciliation_banks MODIFY COLUMN reply_status ENUM('pending', 'received', 'completed') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Geri al: 'completed' değerlerini 'approved' olarak güncelle
        \DB::statement("UPDATE reconciliation_banks SET reply_status = 'approved' WHERE reply_status = 'completed'");
        
        // Eski enum'a geri dön
        \DB::statement("ALTER TABLE reconciliation_banks MODIFY COLUMN reply_status ENUM('pending', 'received', 'approved') DEFAULT 'pending'");
    }
};
