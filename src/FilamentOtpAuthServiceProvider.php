<?php

namespace Saeidsharafi\FilamentOtpAuth;

use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Saeidsharafi\FilamentOtpAuth\Http\Responses\OtpLoginResponse;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;


class FilamentOtpAuthServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-otp-auth';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile() // Loads config/filament-otp-auth.php
            ->hasViews()      // Allows users to override views
            ->hasMigration('2024_01_01_000000_create_otps_table') // Finds the migration
            ->hasTranslations(); // Loads translations
        // ->hasAssets() // If you add CSS/JS assets later
        // ->publishesServiceProvider($name) // If needed
    }

    public function packageBooted(): void
    {
        // Optional: Register Assets if you have them
        // FilamentAsset::register([
        //     Js::make('otp-login-script', __DIR__ . '/../resources/dist/js/script.js'), // Example asset path
        // ], $this->getAssetPackageName());
    }
    public function packageRegistered(): void
    {
        // OPTIONAL: Bind your custom response to the Filament contract.
        // Uncomment this line ONLY if you want your package to force
        // its response implementation by default. Usually, you let
        // the end-user decide if they want to override the contract
        // in their AppServiceProvider. Leaving it commented means
        // Filament's default response handler is used unless the user
        // explicitly overrides it themselves.

         $this->app->bind(
             LoginResponse::class,
             OtpLoginResponse::class
         );
    }
    // Optional: Helper for asset package name
    // protected function getAssetPackageName(): ?string
    // {
    //     return 'saeidsharafi/filament-otp-auth';
    // }
}
