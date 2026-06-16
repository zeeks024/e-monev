<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendResetCode extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public string $code)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode Reset Password E-Monev KIP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.send-reset-code',
            with: ['code' => $this->code],
        );
    }
}
