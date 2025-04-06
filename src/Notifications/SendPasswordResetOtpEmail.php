<?php

namespace SaeidSharafi\FilamentOtpAuth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class SendPasswordResetOtpEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $otp) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(Lang::get('filament-otp-auth::filament-otp-auth.notifications.password_reset_subject'))
            ->line(Lang::get('filament-otp-auth::filament-otp-auth.notifications.password_reset_line1'))
            ->line(Lang::get('filament-otp-auth::filament-otp-auth.notifications.password_reset_line2', ['otp' => $this->otp]))
            ->line(Lang::get('filament-otp-auth::filament-otp-auth.notifications.password_reset_line3', ['minutes' => config('filament-otp-auth.otp_expiry_minutes', 5)]));
        // Optional Action Button:
        // ->action(Lang::get('filament-otp-auth::filament-otp-auth.notifications.password_reset_action_text', ['app_name' => config('app.name')]), url(config('app.url'))) // Or a specific reset URL if needed
        // ->line(Lang::get('filament-otp-auth::filament-otp-auth.notifications.password_reset_salutation'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'otp' => $this->otp, // For database logging or other channels
        ];
    }
}
