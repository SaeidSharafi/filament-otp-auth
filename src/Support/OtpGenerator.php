<?php

declare(strict_types=1);

namespace SaeidSharafi\FilamentOtpAuth\Support;

use Exception;

final class OtpGenerator
{
    public static function generate(): string
    {
        $length = config('filament-otp-auth.otp_length', 6);

        if ($length < 4) {
            $length = 4; // Minimum sensible length
        }

        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;

        try {
            // Use cryptographically secure random number generator
            return (string) random_int($min, $max);
        } catch (Exception $e) {
            // Fallback for environments where random_int is not available (rare)
            return mb_str_pad((string) mt_rand($min, $max), $length, '0', STR_PAD_LEFT);
        }
    }
}
