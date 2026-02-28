<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reconciliation_banks', function (Blueprint $table) {
            $table->boolean('kase_talep_edildi')->default(false)->after('notes')
                ->comment('Banka firmadan kaşe imzalı mektup talep etti');
        });
    }

    public function down(): void
    {
        Schema::table('reconciliation_banks', function (Blueprint $table) {
            $table->dropColumn('kase_talep_edildi');
        });
    }
};
