<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $bookingId,
        public string $tripFrom,
        public string $tripTo,
        public string $tripDate,
        public string $tripTime,
        public string $seatNumber,
        public float  $totalPrice,
        public string $vehicleName,
        public ?string $bookingUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تم تأكيد حجزك بنجاح! ',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmed',
            with: [
                'userName'    => $this->userName,
                'bookingId'   => $this->bookingId,
                'tripFrom'    => $this->tripFrom,
                'tripTo'      => $this->tripTo,
                'tripDate'    => $this->tripDate,
                'tripTime'    => $this->tripTime,
                'seatNumber'  => $this->seatNumber,
                'totalPrice'  => $this->totalPrice,
                'vehicleName' => $this->vehicleName,
                'bookingUrl'  => $this->bookingUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
