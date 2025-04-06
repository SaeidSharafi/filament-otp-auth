<?php
// src/Http/Responses/OtpLoginResponse.php

namespace Saeidsharafi\FilamentOtpAuth\Http\Responses;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract; // Use the correct contract

class OtpLoginResponse implements LoginResponseContract
{
    /**
     * Create a new response instance.
     *
     * @param  \Illuminate\Http\Request $request // Type hint might vary based on context passed
     * @return \Illuminate\Http\RedirectResponse|\Livewire\Features\SupportRedirects\Redirector
     */
    public function toResponse($request): RedirectResponse|Redirector
    {
        // This mimics Filament's default behavior.
        // Users could override this binding in their AppServiceProvider
        // to provide custom logic based on the authenticated user:
        // e.g., auth()->user()->isAdmin() ? redirect(...) : redirect(...);
        return redirect()->intended(Filament::getHomeUrl());
    }
}
