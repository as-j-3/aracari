<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public string $purpose = 'default'
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->purpose) {
            'verify_account' => 'كود التحقق من حسابك - Aracari',
            'reset_password' => 'كود إعادة تعيين كلمة المرور - Aracari',
            'login' => 'كود تسجيل الدخول - Aracari',
            default => 'كود التحقق - Aracari',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: [
                'otp' => $this->otp,
                'purpose' => $this->purpose,
                'purposeText' => $this->getPurposeText(),
                'expiryMinutes' => 10,
            ],
        );
    }

    private function getPurposeText(): string
    {
        return match ($this->purpose) {
            'verify_account' => 'تفعيل حسابك',
            'reset_password' => 'إعادة تعيين كلمة المرور',
            'login' => 'تسجيل الدخول',
            default => 'التحقق',
        };
    }

    public function attachments(): array
    {
        return [];
    }
}
