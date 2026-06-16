<?php

namespace App\Mail;

use App\Models\Pesan;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminPesanMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Pesan $pesan,
        public ?User $user = null,
        public ?string $namaPenerima = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->pesan->judul
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-pesan',
            with: [
                'namaPenerima' => $this->namaPenerima
                    ?? $this->user?->badanPublik?->nama_badan_publik
                    ?? $this->user?->name
                    ?? 'Bapak/Ibu Pengguna E-Monev KIP',
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
