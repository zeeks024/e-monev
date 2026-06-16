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
            $table->string('jenis')->default('custom')->after('judul');
            $table->boolean('kirim_email')->default(false)->after('isi');
            $table->timestamp('email_dikirim_pada')->nullable()->after('kirim_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesans', function (Blueprint $table) {
            $table->dropColumn([
                'jenis',
                'kirim_email',
                'email_dikirim_pada',
            ]);
        });
    }
};
