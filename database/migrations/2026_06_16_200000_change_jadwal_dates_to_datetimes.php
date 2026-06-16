<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->dateTime('tanggal_mulai')->change();
                $table->dateTime('tanggal_selesai')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE jadwals MODIFY tanggal_mulai DATETIME NOT NULL");
        DB::statement("ALTER TABLE jadwals MODIFY tanggal_selesai DATETIME NOT NULL");
        DB::statement("UPDATE jadwals SET tanggal_mulai = CONCAT(DATE(tanggal_mulai), ' 00:00:00') WHERE TIME(tanggal_mulai) = '00:00:00'");
        DB::statement("UPDATE jadwals SET tanggal_selesai = CONCAT(DATE(tanggal_selesai), ' 23:59:59') WHERE TIME(tanggal_selesai) = '00:00:00'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->date('tanggal_mulai')->change();
                $table->date('tanggal_selesai')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE jadwals MODIFY tanggal_mulai DATE NOT NULL");
        DB::statement("ALTER TABLE jadwals MODIFY tanggal_selesai DATE NOT NULL");
    }
};
