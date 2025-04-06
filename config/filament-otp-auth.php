<?php

declare(strict_types=1);

use App\Models\User; // Make sure this points to your application's User model
use SaeidSharafi\FilamentOtpAuth\Notifications\SendOtpEmail;
use SaeidSharafi\FilamentOtpAuth\Notifications\SendPasswordResetOtpEmail;
use SaeidSharafi\FilamentOtpAuth\Filament\Pages\FilamentOtpAuth;
use SaeidSharafi\FilamentOtpAuth\Models\Otp as DefaultOtpModel;

return [

    /*
    |--------------------------------------------------------------------------
    | Authenticatable Model
    |--------------------------------------------------------------------------
    */
    'authenticatable' => User::class,

    /*
    |--------------------------------------------------------------------------
    | Identifier Column(s)
    |--------------------------------------------------------------------------
    */
    'email_column' => 'email',
    'phone_column' => 'phone', // Set to null or comment out if phone login is disabled

    /*
    |--------------------------------------------------------------------------
    | Login/Registration OTP Notifications
    |--------------------------------------------------------------------------
    */
    'email_notification' => SendOtpEmail::class,
    'sms_notification'   => null, // Specify your SMS notification class for login/registration here if used

    /*
    |--------------------------------------------------------------------------
    | Password Reset OTP Notifications
    |--------------------------------------------------------------------------
    */
    'password_reset_email_notification' => SendPasswordResetOtpEmail::class,
    'password_reset_sms_notification'   => null, // Specify your SMS notification class for password reset here if used

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    */
    'otp_length'               => 6,
    'otp_expiry_minutes'       => 5,
    'otp_resend_delay_seconds' => 60, // Cooldown in seconds before OTP can be resent

    /*
    |--------------------------------------------------------------------------
    | OTP Storage
    |--------------------------------------------------------------------------
    */
    'otp_table' => 'otps',
    'otp_model' => DefaultOtpModel::class,

    /*
    |--------------------------------------------------------------------------
    | Throttling Configuration
    |--------------------------------------------------------------------------
    */
    'throttle_max_attempts'  => 5,    // Max OTP requests (per identifier) within decay period
    'throttle_decay_minutes' => 1,     // Time window (in minutes) for max attempts

    /*
    |--------------------------------------------------------------------------
    | User Creation
    |--------------------------------------------------------------------------
    */
    'create_user_if_not_exists' => true,

    /*
    |--------------------------------------------------------------------------
    | Phone Number Validation Regex
    |--------------------------------------------------------------------------
    */
    'phone_regex' => '/^(\+?\d{1,4}[\s-]?)?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}$/', // Adjust as needed for your formats

    /*
    |--------------------------------------------------------------------------
    | Filament Page Customization
    |--------------------------------------------------------------------------
    */
    'login_page_class' => FilamentOtpAuth::class,

];
