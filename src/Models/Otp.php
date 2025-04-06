<?php

declare(strict_types=1);

namespace SaeidSharafi\FilamentOtpAuth\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class Otp extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('filament-otp-auth.otp_table', 'otps');
    }

    /**
     * Scope to find valid OTPs.
     *
     * @param  Builder<Otp>  $query  The Eloquent query builder instance for the Otp model.
     * @param  string  $identifier  The identifier (email/phone) to check.
     * @param  string  $code  The OTP code to verify.
     * @return Builder<Otp> The modified query builder instance.
     */
    public function scopeValid(Builder $query, string $identifier, string $code): Builder
    {
        return $query->where('identifier', $identifier)
            ->where('code', $code)
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope to check if any valid OTP exists for the identifier.
     *
     * @param  Builder<Otp>  $query  The Eloquent query builder instance for the Otp model.
     * @param  string  $identifier  The identifier (email/phone) to check.
     */
    public function scopeHasAnyValid(Builder $query, string $identifier): void
    {
        $query->where('identifier', $identifier)
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope to find the latest OTP for an identifier (useful for resend checks).
     *
     * @param  Builder<Otp>  $query  The Eloquent query builder instance for the Otp model.
     * @param  string  $identifier  The identifier (email/phone).
     * @return Builder<Otp> The modified query builder instance.
     */
    public function scopeLatestFor(Builder $query, string $identifier): Builder
    {
        return $query->where('identifier', $identifier)->latest('created_at');
    }
}
