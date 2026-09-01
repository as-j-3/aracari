<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $resetLink,
        public int    $expiryMinutes = 30,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'إعادة تعيين كلمة المرور ',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
            with: [
                'userName'      => $this->userName,
                'resetLink'     => $this->resetLink,
                'expiryMinutes' => $this->expiryMinutes,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
