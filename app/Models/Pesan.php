<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Pesan extends Model
{
    use HasFactory;

    protected $fillable = [
        'judul',
        'jenis',
        'isi',
        'kirim_email',
        'kirim_aplikasi',
        'jumlah_penerima',
        'jumlah_email_terkirim',
        'email_dikirim_pada',
    ];

    protected $casts = [
        'created_at' => 'datetime:d F Y H:i',
        'updated_at' => 'datetime:d F Y H:i',
        'email_dikirim_pada' => 'datetime:d F Y H:i',
        'kirim_email' => 'boolean',
        'kirim_aplikasi' => 'boolean',
        'jumlah_penerima' => 'integer',
        'jumlah_email_terkirim' => 'integer',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pesan_user')
                    ->withTimestamps()
                    ->withPivot('dibaca_pada');
    }
}
