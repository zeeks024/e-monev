<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalPertanyaan extends Model
{
    use HasFactory;

    protected $fillable = [
        'jadwal_id',
        'pertanyaan_template_id',
        'teks_pertanyaan',
        'definisi_operasional',
        'urutan',
        'tipe_jawaban',
        'butuh_link',
        'butuh_upload',
        'skor_maks',
    ];

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function pertanyaanTemplate(): BelongsTo
    {
        return $this->belongsTo(PertanyaanTemplate::class);
    }

    public function jawabans(): HasMany
    {
        return $this->hasMany(Jawaban::class);
    }
}
