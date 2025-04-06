<?php

declare(strict_types=1);

namespace SaeidSharafi\FilamentOtpAuth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class Otp extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory; // Optional: If you want factories

    public $timestamps = false; // We only use created_at manually for resend logic
    protected $guarded = []; // Allow mass assignment for simplicity here

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('filament-otp-auth.otp_table', 'otps');
    }

    // Scope to find valid OTPs
    public function scopeValid(Builder $query, string $identifier, string $code): Builder
    {
        return $query->where('identifier', $identifier)
            ->where('code', $code)
            ->where('expires_at', '>', Carbon::now());
    }
    public function scopeHasAnyValid(Builder $query, string $identifier): void
    {
        $query->where('identifier', $identifier)
            ->where('expires_at', '>', Carbon::now());
    }
    // Scope to find the latest OTP for resend check
    public function scopeLatestFor(Builder $query, string $identifier): Builder
    {
        return $query->where('identifier', $identifier)->latest('created_at');
    }
}
