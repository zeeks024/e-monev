<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_pertanyaans', function (Blueprint $table) {
            $table->integer('skor_maks')->default(0)->after('definisi_operasional');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_pertanyaans', function (Blueprint $table) {
            $table->dropColumn('skor_maks');
        });
    }
};
