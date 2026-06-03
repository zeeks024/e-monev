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
        Schema::table('jawabans', function (Blueprint $table) {
            $table->text('catatan')->nullable()->after('upload_dokumen');
            $table->boolean('is_valid')->nullable()->default(null)->after('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('jawabans', function (Blueprint $table) {
            $table->dropColumn(['catatan', 'is_valid']);
        });
    }
};
