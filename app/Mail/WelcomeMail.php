<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public ?string $loginUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'أهلاً بك في أراكاري! ',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'userName' => $this->userName,
                'loginUrl' => $this->loginUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
