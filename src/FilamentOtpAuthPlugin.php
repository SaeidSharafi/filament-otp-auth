<?php

declare(strict_types=1);

namespace Saeidsharafi\FilamentOtpAuth;

use Filament\Contracts\Plugin;
use Filament\Panel;
use SaeidSharafi\FilamentOtpAuth\Filament\Pages\FilamentOtpAuth;

 // Import the page class

class FilamentOtpAuthPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-otp-auth';
    }

    public function register(Panel $panel): void
    {
        // Register the custom login page
        $loginPageClass = config('filament-otp-auth.login_page_class', FilamentOtpAuth::class);

        $panel
            ->authGuard(config('filament.auth.guard', 'web')) // Use the default guard or allow override?
            ->login($loginPageClass); // Set our custom page as the login route

        // Optionally register other components like custom fields if you add them later
    }

    public function boot(Panel $panel): void
    {
        // Perform any actions after the panel is booted
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
