<?php

declare(strict_types=1);

namespace SaeidSharafi\FilamentOtpAuth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SendOtpEmail extends Notification implements ShouldQueue // Implement ShouldQueue for background sending
{
    use Queueable;

    public function __construct(public string $otp) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('filament-otp-auth::filament-otp-auth.notifications.subject'))
            ->line(__('filament-otp-auth::filament-otp-auth.notifications.line', ['otp' => $this->otp]));
    }

    /**
     * @return string[]
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
        return __('filament-otp-auth::filament-otp-auth.notifications.sms_line', ['otp' => $this->otp]);
    }
    */
    // It's generally cleaner to have separate Notification classes for Mail and SMS configured by the user.
}
