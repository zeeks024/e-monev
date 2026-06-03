<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jawaban extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'jadwal_pertanyaan_id',
        'jawaban',
        'link_dokumen',
        'upload_dokumen',
        'catatan',
        'is_valid',
    ];

    protected $casts = [
        'is_valid' => \App\Casts\ThreeStateBoolean::class,
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function jadwalPertanyaan(): BelongsTo
    {
        return $this->belongsTo(JadwalPertanyaan::class);
    }
}
