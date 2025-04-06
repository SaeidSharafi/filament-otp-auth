<?php

declare(strict_types=1);

namespace SaeidSharafi\FilamentOtpAuth\Contracts;

use Illuminate\Contracts\Routing\UrlRoutable; // For key retrieval

 // If using the trait is convenient

interface OtpNotifiable extends UrlRoutable
{
    // Method(s) to provide routing information for notifications (mail, sms channels)
    // Example:
    public function routeNotificationForMail($notification): ?string;
    // public function routeNotificationForVonage($notification): ?string;
    // public function routeNotificationFor<YourSmsChannel>($notification): ?string;

    // Potentially other methods needed by your notification classes
    // public function getNameForOtpGreeting(): string;
}

// Your User model should implicitly satisfy this if it uses Notifiable and has email/phone.
// This interface is primarily for the anonymous notifiable used when the user doesn't exist yet.
