<?php

declare(strict_types=1);

namespace SaeidSharafi\FilamentOtpAuth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class SendOtpEmail extends Notification implements ShouldQueue // Implement ShouldQueue for background sending
{
    use Queueable;

    public function __construct(public string $otp)
    {
    }

    /**
     * Get the notification's delivery channels.
     * We expect the calling code (InteractsWithOtp trait) to determine
     * the correct channel based on the identifier type.
     */
    public function via(object $notifiable): array
    {
        // The trait will determine if 'mail' or an SMS channel should be used.
        // This default assumes 'mail', but the user's config can override this class.
        // If using a single class, you might inspect $notifiable here.
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(Lang::get('filament-otp-auth::filament-otp-auth.notifications.subject'))
            ->line(Lang::get('filament-otp-auth::filament-otp-auth.notifications.line', ['otp' => $this->otp]));
    }

    /**
     * Get the array representation of the notification. (Optional)
     */
    public function toArray(object $notifiable): array
    {
        return [
            'otp' => $this->otp,
        ];
    }

    /**
     * Helper to get a name for the greeting.
     */
    protected function getNotifiableName(object $notifiable): string
    {
        return $notifiable->name ?? $notifiable->email ?? $notifiable->phone ?? 'User';
    }

    // --- Placeholder for SMS ---
    // If you want this default class to handle SMS too, you would add:
    // 1. Check `via()` method to return the correct SMS channel alias (e.g., 'vonage', 'twilio').
    // 2. Add a `toVonage()` or `toTwilio()` method (depending on the driver).
    // Example for a generic SMS channel:
    /*
    public function toSms(object $notifiable): string // Return value depends on SMS driver
    {
        return Lang::get('filament-otp-auth::filament-otp-auth.notifications.sms_line', ['otp' => $this->otp]);
    }
    */
    // It's generally cleaner to have separate Notification classes for Mail and SMS configured by the user.
}
