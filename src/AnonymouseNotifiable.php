<?php

namespace Saeidsharafi\FilamentOtpAuth;

class AnonymouseNotifiable implements \SaeidSharafi\FilamentOtpAuth\Contracts\OtpNotifiable
{

    use \Illuminate\Notifications\Notifiable;

    public function __construct(public string $identifierValue, public string $notifyType)
    {
    }

    public function routeNotificationForMail($notification): ?string
    {
        return $this->notifyType === 'email' ? $this->identifierValue : null;
    }

    public function routeNotificationForVonage($notification): ?string
    { // Example SMS channel
        return $this->notifyType === 'phone' ? $this->identifierValue : null;
    }

    public function routeNotificationForTwilio($notification): ?string
    { // Example SMS channel
        return $this->notifyType === 'phone' ? $this->identifierValue : null;
    }

    public function getKey()
    {
        return $this->identifierValue;
    } // Needed by Notifiable

    public function getRouteKey()
    {
        // TODO: Implement getRouteKey() method.
    }

    public function getRouteKeyName()
    {
        // TODO: Implement getRouteKeyName() method.
    }

    public function resolveRouteBinding($value, $field = null)
    {
        // TODO: Implement resolveRouteBinding() method.
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        // TODO: Implement resolveChildRouteBinding() method.
    }
}
