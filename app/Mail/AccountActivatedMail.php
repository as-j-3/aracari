<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تم تفعيل حسابك - أهلاً بك في أراكاري',
            to: [$this->user->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-activated',
            with: ['user' => $this->user]
        );
    }
}