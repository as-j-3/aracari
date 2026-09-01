<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\OtpEmail;
use App\Mail\WelcomeMail;
use App\Mail\PasswordChangedMail;
use App\Mail\AccountActivatedMail;
use App\Mail\BookingConfirmedMail;
use App\Mail\BookingCancelledMail;
use App\Mail\ResetPasswordMail;

class EmailService
{
    public function sendOtp(string $email, string $otp, string $purpose = 'default'): bool
    {
        try {
            Mail::mailer('verify')->to($email)->send(new OtpEmail($otp, $purpose));

            Log::info('OTP email sent successfully', [
                'email' => $email,
                'purpose' => $purpose,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email', [
                'email' => $email,
                'purpose' => $purpose,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendWelcome(User|string $userOrEmail, string $userName): bool
    {
        try {
            $email = $userOrEmail instanceof User ? $userOrEmail->email : $userOrEmail;

            Mail::to($email)->send(new WelcomeMail(
                userName: $userName,
            ));

            Log::info('Welcome email sent', ['email' => $email]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
                'email' => $userOrEmail instanceof User ? $userOrEmail->email : $userOrEmail,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendPasswordChanged(User $user): bool
    {
        try {
            Mail::to($user->email)->send(new PasswordChangedMail($user));

            Log::info('Password changed email sent', ['user_id' => $user->id]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send password changed email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendAccountActivated(User $user): bool
    {
        try {
            Mail::to($user->email)->send(new AccountActivatedMail($user));

            Log::info('Account activated email sent', ['user_id' => $user->id]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send account activated email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendBookingConfirmed(User $user, Booking $booking): bool
    {
        try {
            Mail::to($user->email)->send(new BookingConfirmedMail(
                userName: $user->name ?? 'مستخدم',
                bookingId: $booking->id,
                tripFrom: $booking->trip?->fromCity?->name ?? '—',
                tripTo: $booking->trip?->toCity?->name ?? '—',
                tripDate: $booking->trip?->departure_date?->format('Y/m/d') ?? '—',
                tripTime: $booking->trip?->departure_time ?? '—',
                seatNumber: $booking->seat?->seat_number ?? '—',
                totalPrice: $booking->total_price ?? 0,
                vehicleName: $booking->trip?->vehicle?->plate_number ?? '—',
            ));

            Log::info('Booking confirmed email sent', [
                'user_id' => $user->id,
                'booking_id' => $booking->id,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send booking confirmed email', [
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendBookingCancelled(
        User $user,
        Booking $booking,
        float $refundAmount = 0,
        float $penaltyAmount = 0,
        ?string $cancelReason = null,
    ): bool {
        try {
            Mail::to($user->email)->send(new BookingCancelledMail(
                userName: $user->name ?? 'مستخدم',
                bookingId: $booking->id,
                tripFrom: $booking->trip?->fromCity?->name ?? '—',
                tripTo: $booking->trip?->toCity?->name ?? '—',
                refundAmount: $refundAmount,
                penaltyAmount: $penaltyAmount,
                cancelReason: $cancelReason,
            ));

            Log::info('Booking cancelled email sent', [
                'user_id' => $user->id,
                'booking_id' => $booking->id,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send booking cancelled email', [
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendResetPassword(User $user, string $resetLink, int $expiryMinutes = 30): bool
    {
        try {
            Mail::to($user->email)->send(new ResetPasswordMail(
                userName: $user->name ?? 'مستخدم',
                resetLink: $resetLink,
                expiryMinutes: $expiryMinutes,
            ));

            Log::info('Reset password email sent', ['user_id' => $user->id]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send reset password email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendEmail(string $to, string $subject, string $content, array $data = []): bool
    {
        try {
            Mail::mailer('verify')->raw($content, function ($message) use ($to, $subject) {
                $message->to($to)
                    ->subject($subject);
            });

            Log::info('Email sent successfully', [
                'to' => $to,
                'subject' => $subject,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

