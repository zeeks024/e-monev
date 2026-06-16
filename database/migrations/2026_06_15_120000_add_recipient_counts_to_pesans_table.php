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
        Schema::table('pesans', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_penerima')->nullable()->after('kirim_aplikasi');
            $table->unsignedInteger('jumlah_email_terkirim')->nullable()->after('jumlah_penerima');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesans', function (Blueprint $table) {
            $table->dropColumn([
                'jumlah_penerima',
                'jumlah_email_terkirim',
            ]);
        });
    }
};
