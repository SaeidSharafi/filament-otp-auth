# Filament OTP Authentication

[![Latest Version on Packagist](https://img.shields.io/packagist/v/saeidsharafi/filament-otp-auth.svg?style=flat-square)](https://packagist.org/packages/saeidsharafi/filament-otp-auth)
[![Total Downloads](https://img.shields.io/packagist/dt/saeidsharafi/filament-otp-auth.svg?style=flat-square)](https://packagist.org/packages/saeidsharafi/filament-otp-auth)

![Screenshot](https://banners.beyondco.de/Filament%20OTP%20Authentication.png?theme=light&packageManager=composer+require&packageName=saeidsharafi%2Ffilament-otp-auth&pattern=architect&style=style_1&description=Authenticate+with+OTP+via+Email+or+Phone+for+FilamentPHP&md=1&showWatermark=0&fontSize=100px&images=email)

This package provides a flexible OTP (One-Time Password) authentication solution for FilamentPHP. It allows users to log in using either their email or phone number, and receive an OTP code for verification.  Users that have already set a password can use that for normal username/password authentication.  This also provides an OTP-based forgot password flow.

## Key Features

*   **Email or Phone Authentication:** Supports login via email or phone number.
*   **OTP Verification:**  Replaces traditional passwords with secure OTP codes.
*   **Password Support:** Users with stored passwords can still log in via username/password.
*   **Forgot Password Flow:** Allows users to reset their passwords using OTP verification.
*   **Customizable:** Offers extensive configuration options, including OTP length, expiry, and notification methods.
*   **Rate Limiting:**  Protects against brute-force attacks with configurable rate limiting.
*   **User Creation:**  Optionally creates new users if they don't exist in the database.
*   **Filament Integration:** Seamlessly integrates into your FilamentPHP admin panel.
*   **Customizable Login Page:**  Override the default login page for a tailored user experience.

## Installation

You can install the package via composer:

```bash
composer require saeidsharafi/filament-otp-auth
```

Publish the configuration file:
```bash
php artisan vendor:publish --tag="filament-otp-auth-config"
```

Publish and run the migrations:
```bash
php artisan vendor:publish --tag="filament-otp-auth-translations"
```

## Configuration

After publishing the configuration file (`config/filament-otp-auth.php`), you can customize the package's behavior.  Here are some of the most important configuration options:

*   **`authenticatable`**:  Specifies the Eloquent model representing your users (e.g., `App\Models\User::class`).  Ensure this model uses the `Notifiable` trait.

*   **`email_column` and `phone_column`**: Define the database column names used for email and phone number lookups in your user model (e.g., `'email'` and `'phone'`). Set `phone_column` to `null` if you don't support phone number authentication.

*   **`email_notification` and `sms_notification`**: Specify the notification classes used to send OTP codes via email and SMS.  These classes must extend `Illuminate\Notifications\Notification`. An example `SendOtpEmail` class is provided with the package.

*   **`otp_length`**, **`otp_expiry_minutes`**, and **`otp_resend_delay_seconds`**: Configure the OTP's length, validity duration (in minutes), and the minimum time (in seconds) a user must wait before requesting a new OTP.

*   **`create_user_if_not_exists`**:  Set to `true` to automatically create a new user if the provided email or phone number doesn't exist in the database.  Ensure your User model's fillable property includes the identifier column(s).

*   **`login_page_class`**:  Allows you to specify a custom Filament page class for the login screen.  This allows for extensive customization of the login experience. The default is `\SaeidSharafi\FilamentOtpAuth\Filament\Pages\FilamentOtpAuth::class`.

## Usage

No plugin registration is required, since the Filament page is registered directly in the config file.

**1. Configure Your User Model:**

Ensure your `User` model (or the model specified in `authenticatable`) uses the `Notifiable` trait and has the `email` and `phone` (if you are using phone authentication) columns fillable.

**2. Define Notification Classes:**

Create notification classes (e.g., `SendOtpEmail`) that extend `Illuminate\Notifications\Notification` and handle sending the OTP code via email and/or SMS.  The example `SendOtpEmail` class is provided with the package.

**3. (Optional) Customize the Login Page:**

If you want to customize the login page, you can extend the `\SaeidSharafi\FilamentOtpAuth\Filament\Pages\FilamentOtpAuth` page and set your custom login page to plugin in the `login_page_class` setting in the config file.

```php
<?php

namespace App\Filament\Pages;

use SaeidSharafi\FilamentOtpAuth\Filament\Pages\FilamentOtpAuth as OtpLogin;
use Illuminate\Contracts\Support\Htmlable;

class OverrideLogin extends OtpLogin
{
    public function getHeading(): string | Htmlable
    {
        return 'Example Login Heading';
    }
}
```

Then update the config/filament-otp-auth.php with the classname:
```php
    'login_page_class' => \App\Filament\Pages\OverrideLogin::class,
```

## Localization
Make sure to publish the translation files and modify them for your desired languages.

## Testing
```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Saeid Sharafi](https://github.com/SaeidSharafi)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Supported By

<a href="https://www.jetbrains.com/phpstorm/" target="_blank"><img src="https://res.cloudinary.com/rupadana/image/upload/v1707040287/phpstorm_xjblau.png" width="50px" height="50px"></img></a>
