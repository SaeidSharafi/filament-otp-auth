<?php

declare(strict_types=1);

namespace SaeidSharafi\FilamentOtpAuth\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
// Assuming this exists, might need removal if not used
use Saeidsharafi\FilamentOtpAuth\Support\OtpGenerator;
use Saeidsharafi\FilamentOtpAuth\Models\Otp as OtpModel;
use Saeidsharafi\FilamentOtpAuth\Exceptions\OtpException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SaeidSharafi\FilamentOtpAuth\Notifications\SendPasswordResetOtpEmail;
use Exception;

 // Import the new notification

trait InteractsWithOtp
{
    protected function getAuthenticatableModel(): string
    {
        return config('filament-otp-auth.authenticatable');
    }

    protected function getOtpModel(): string
    {
        // Ensure the model class exists or provide a default
        return config('filament-otp-auth.otp_model', OtpModel::class);
    }

    protected function getIdentifierType(string $identifier): string
    {
        $phoneRegex = config('filament-otp-auth.phone_regex');
        if ($phoneRegex && preg_match($phoneRegex, $identifier) && config('filament-otp-auth.phone_column')) {
            return 'phone';
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }

        // Default or assume based on configuration priorities if needed
        return 'email'; // Or throw an exception if format is strictly required earlier
    }

    protected function getIdentifierColumn(string $type): ?string
    {
        return match ($type) {
            'email' => config('filament-otp-auth.email_column', 'email'),
            'phone' => config('filament-otp-auth.phone_column'),
            default => null,
        };
    }

    protected function getNotificationClass(string $type): ?string
    {
        return match ($type) {
            'email' => config('filament-otp-auth.email_notification'),
            'phone' => config('filament-otp-auth.sms_notification'),
            default => null,
        };
    }

    protected function getPasswordResetNotificationClass(string $type): ?string
    {
        return match ($type) {
            'email' => config('filament-otp-auth.password_reset_email_notification', SendPasswordResetOtpEmail::class),
            'phone' => config('filament-otp-auth.password_reset_sms_notification'),
            default => null,
        };
    }

    protected function getOtpThrottleKey(string $identifier): string
    {
        return 'otp_request:'.Str::lower($identifier);
    }

    protected function getOtpResendThrottleKey(string $identifier): string
    {
        return 'otp_resend:'.Str::lower($identifier);
    }

    public function canRequestOtp(string $identifier): bool
    {
        $resendKey   = $this->getOtpResendThrottleKey($identifier);
        $requestKey  = $this->getOtpThrottleKey($identifier);
        $maxAttempts = config('filament-otp-auth.throttle_max_attempts', 5);

        if (RateLimiter::tooManyAttempts($requestKey, $maxAttempts)) {
            return false;
        }
        return ! (RateLimiter::tooManyAttempts($resendKey, 1))



        ;
    }

    public function generateAndSendOtp(string $identifier, ?Authenticatable $user = null, bool $resend = false): void
    {
        if ( ! $resend && $this->getOtpModel()::where('identifier', $identifier)
            ->where('expires_at', '>', Carbon::now())->exists()) {
            // OTP already exists and is valid, maybe just resend notification or do nothing?
            // For now, let's return to avoid generating a new one unless explicitly resending.
            // If you want to always generate a new one, remove this block or adjust logic.
            return;
        }
        if ( ! $this->canRequestOtp($identifier)) {
            $seconds = RateLimiter::availableIn($this->getOtpResendThrottleKey($identifier));
            throw OtpException::tooManyAttempts($seconds);
        }

        $identifierType    = $this->getIdentifierType($identifier);
        $identifierColumn  = $this->getIdentifierColumn($identifierType);
        $notificationClass = $this->getNotificationClass($identifierType);

        if ( ! $identifierColumn || ! $notificationClass) {
            throw OtpException::configurationError("Invalid identifier type or missing configuration for '{$identifierType}'.");
        }

        DB::beginTransaction();

        try {
            if ( ! $user) {
                $authenticatableModel = $this->getAuthenticatableModel();
                $user                 = $authenticatableModel::firstWhere($identifierColumn, $identifier);

                if ( ! $user && config('filament-otp-auth.create_user_if_not_exists', true)) {
                    $user = $authenticatableModel::create([
                        $identifierColumn => $identifier,
                        'password'        => null, // Explicitly set password null for OTP-only users if desired
                    ]);
                    if ( ! $user) {
                        throw new Exception("Failed to create user for identifier: {$identifier}");
                    }
                } elseif ( ! $user) {
                    throw OtpException::userNotFound();
                }
            }

            // Invalidate previous OTPs for this identifier before creating a new one
            $this->getOtpModel()::where('identifier', $identifier)->delete();

            $otpCode            = OtpGenerator::generate();
            $otpExpiryMinutes   = config('filament-otp-auth.otp_expiry_minutes', 5);
            $resendDelaySeconds = config('filament-otp-auth.otp_resend_delay_seconds', 60);

            $this->getOtpModel()::create([
                'identifier' => $identifier,
                'code'       => $otpCode,
                'expires_at' => Carbon::now()->addMinutes($otpExpiryMinutes),
                'created_at' => Carbon::now(),
            ]);

            try {
                Notification::sendNow($user, new $notificationClass($otpCode));
            } catch (Exception $e) {
                Log::error("OTP Notification failed for {$identifier}: ".$e->getMessage());
                throw OtpException::notificationFailed($e);
            }

            DB::commit();

            RateLimiter::hit($this->getOtpThrottleKey($identifier), config('filament-otp-auth.throttle_decay_minutes', 1) * 60);
            RateLimiter::hit($this->getOtpResendThrottleKey($identifier), $resendDelaySeconds);

        } catch (Exception $e) {
            DB::rollBack();
            // Log error even if it's an OtpException
            Log::error("OTP generation/sending failed for {$identifier}: ".$e->getMessage(), ['exception' => $e]);
            if ($e instanceof OtpException) {
                throw $e;
            }
            throw OtpException::unexpectedError($e);
        }
    }

    public function generateAndSendPasswordResetOtp(string $identifier, Authenticatable $user, bool $resend = false): void
    {
        // No need to check for existing OTP here if password reset should always generate a new one
        // if (!$resend && ...)

        if ( ! $this->canRequestOtp($identifier)) { // Re-use same basic throttle check
            $seconds = RateLimiter::availableIn($this->getOtpResendThrottleKey($identifier));
            throw OtpException::tooManyAttempts($seconds);
        }

        $identifierType    = $this->getIdentifierType($identifier);
        $notificationClass = $this->getPasswordResetNotificationClass($identifierType);

        if ( ! $notificationClass) {
            throw OtpException::configurationError("Missing password reset notification configuration for '{$identifierType}'.");
        }

        DB::beginTransaction();

        try {
            // Invalidate previous OTPs is still a good idea
            $this->getOtpModel()::where('identifier', $identifier)->delete();

            $otpCode            = OtpGenerator::generate();
            $otpExpiryMinutes   = config('filament-otp-auth.otp_expiry_minutes', 5);
            $resendDelaySeconds = config('filament-otp-auth.otp_resend_delay_seconds', 60);

            // Create or update OTP record
            $this->getOtpModel()::create([ // Using create after delete ensures clean state
                'identifier' => $identifier,
                'code'       => $otpCode,
                'expires_at' => Carbon::now()->addMinutes($otpExpiryMinutes),
                'created_at' => Carbon::now(),
            ]);

            try {
                Notification::sendNow($user, new $notificationClass($otpCode));
            } catch (Exception $e) {
                Log::error("Password Reset OTP Notification failed for {$identifier}: ".$e->getMessage());
                throw OtpException::notificationFailed($e);
            }

            DB::commit();

            // Hit same rate limiters, or use specific ones if needed
            RateLimiter::hit($this->getOtpThrottleKey($identifier), config('filament-otp-auth.throttle_decay_minutes', 1) * 60);
            RateLimiter::hit($this->getOtpResendThrottleKey($identifier), $resendDelaySeconds);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Password Reset OTP generation/sending failed for {$identifier}: ".$e->getMessage(), ['exception' => $e]);
            if ($e instanceof OtpException) {
                throw $e;
            }
            throw OtpException::unexpectedError($e);
        }
    }

    public function verifyOtp(string $identifier, string $code): bool
    {
        /** @var OtpModel|null $otpRecord */
        $otpRecord = $this->getOtpModel()::valid($identifier, $code)->first();

        if ($otpRecord) {
            $otpRecord->delete();
            // Optionally clear rate limiter on success? Probably not wise.
            return true;
        }

        // Optionally hit a verification attempt throttle here
        // RateLimiter::hit('otp_verify_attempt:' . Str::lower($identifier));

        return false;
    }

    public function verifyPasswordResetOtp(string $identifier, string $code): bool
    {
        // For now, uses the exact same logic as verifyOtp.
        // If OTPs stored a 'purpose', you would check it here.
        return $this->verifyOtp($identifier, $code);
    }


    public function getLastOtpSentAt(string $identifier): ?Carbon
    {
        $otpRecord = $this->getOtpModel()::where('identifier', $identifier)
            ->orderBy('created_at', 'desc')
            ->first();
        if ($otpRecord) {
            return $otpRecord->created_at;
        }
        return null;
    }
}
