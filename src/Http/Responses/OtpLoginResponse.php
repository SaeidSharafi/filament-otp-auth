<?php

declare(strict_types=1);
// src/Http/Responses/OtpLoginResponse.php

namespace SaeidSharafi\FilamentOtpAuth\Http\Responses;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;

// Use the correct contract

final class OtpLoginResponse implements LoginResponseContract
{
    /**
     * Create a new response instance.
     *
     * @param  \Illuminate\Http\Request  $request  // Type hint might vary based on context passed
     */
    public function toResponse($request): RedirectResponse
    {
        // This mimics Filament's default behavior.
        // Users could override this binding in their AppServiceProvider
        // to provide custom logic based on the authenticated user:
        // e.g., auth()->user()->isAdmin() ? redirect(...) : redirect(...);
        return redirect()->intended(Filament::getHomeUrl());
    }
}
