<div>
    {{-- Optional: Add Filament's logo component --}}
    @if(method_exists($this, 'hasLogo') && $this->hasLogo())
        <div class="flex justify-center mb-8 filament-logo">
            <x-filament-panels::logo/>
        </div>
    @endif

    <x-filament-panels::form wire:submit.prevent="{{$this->submitMethod}}">
        {{ $this->form }}
        <div class="fi-form-actions">
            <div @class([
                 'fi-ac gap-3 flex flex-wrap items-center',
                 match(config('filament.layout.forms.actions.alignment')) {
                    'center' => 'justify-center',
                    'right' => 'justify-end',
                    default => 'justify-start',
                 },
                 'full-width' => 'w-full', // Check if you need full-width based on config/props
             ])>
                @foreach ($this->getFormActions() as $action)
                    {{-- Check if this is the Resend OTP action --}}
                    @if ($action->getName() === 'resendOtp')
                        {{-- Use your custom Blade component --}}
                        {{-- Make sure the namespace matches your package --}}
                        <x-filament-otp-auth::otp-resend-button :action="$action" />
                    @else
                        {{-- Render all other actions normally using Filament's magic --}}
                        {{ $action }}
                    @endif
                @endforeach
            </div>
        </div>
    </x-filament-panels::form>

</div>
