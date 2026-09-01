<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string  $userName,
        public string  $bookingId,
        public string  $tripFrom,
        public string  $tripTo,
        public float   $refundAmount = 0,
        public float   $penaltyAmount = 0,
        public ?string $cancelReason = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تم إلغاء حجزك ',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-cancelled',
            with: [
                'userName'      => $this->userName,
                'bookingId'     => $this->bookingId,
                'tripFrom'      => $this->tripFrom,
                'tripTo'        => $this->tripTo,
                'refundAmount'  => $this->refundAmount,
                'penaltyAmount' => $this->penaltyAmount,
                'cancelReason'  => $this->cancelReason,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
