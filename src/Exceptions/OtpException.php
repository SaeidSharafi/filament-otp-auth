<?php

declare(strict_types=1);

namespace SaeidSharafi\FilamentOtpAuth\Exceptions;

use Exception;
use Throwable;

final class OtpException extends Exception
{
    public static function tooManyAttempts(int $retryAfterSeconds): self
    {
        $message = __('filament-otp-auth::filament-otp-auth.exceptions.too_many_attempts', ['seconds' => $retryAfterSeconds]);

        return new self($message);
    }

    public static function invalidOtp(): self
    {
        $message = __('filament-otp-auth::filament-otp-auth.exceptions.invalid_otp');

        return new self($message);
    }

    public static function expiredOtp(): self
    {
        $message = __('filament-otp-auth::filament-otp-auth.exceptions.expired_otp');

        return new self($message); // Note: verifyOtp checks expiry, so this might not be needed separately often
    }

    public static function notificationFailed(?Throwable $previous = null): self
    {
        $message = __('filament-otp-auth::filament-otp-auth.exceptions.notification_failed');

        return new self($message, 0, $previous);
    }

    public static function userNotFound(): self
    {
        $message = __('filament-otp-auth::filament-otp-auth.exceptions.user_not_found');

        return new self($message);
    }

    public static function configurationError(string $details): self
    {
        $message = __('filament-otp-auth::filament-otp-auth.exceptions.config_error', ['details' => $details]);

        return new self($message);
    }

    public static function unexpectedError(?Throwable $previous = null): self
    {
        $message = __('filament-otp-auth::filament-otp-auth.exceptions.unexpected_error');

        return new self($message, 0, $previous);
    }
}
