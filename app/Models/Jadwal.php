<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Jadwal extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'tahun',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_active',
        'deskripsi',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
    ];

    public function jadwalPertanyaans(): HasMany
    {
        return $this->hasMany(JadwalPertanyaan::class)->orderBy('urutan');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function hasilPenilaians(): HasMany
    {
        return $this->hasMany(HasilPenilaian::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('tanggal_mulai', '<=', now())
            ->where('tanggal_selesai', '>=', now());
    }
}
