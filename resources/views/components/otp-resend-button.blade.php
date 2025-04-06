@props([
    'action', // Expect the Filament Action object as a prop
])

<div
        x-data="{
        timerRunning: false,
        secondsRemaining: 0,
        intervalId: null,
        cooldownDuration: {{ (int) config('filament-otp-auth.resend_otp_cooldown_seconds', 0) }},
        initialCooldown: @entangle('initialResendCooldownSeconds'),

        startTimer(duration) {
            if (this.intervalId) {
                clearInterval(this.intervalId);
            }
            if (duration <= 0) {
                this.timerRunning = false;
                this.secondsRemaining = 0;
                return;
            }
            this.timerRunning = true;
            this.secondsRemaining = duration;
            this.cooldownDuration = duration;
            this.intervalId = setInterval(() => {
                if (this.secondsRemaining > 0) {
                    this.secondsRemaining--;
                } else {
                    this.timerRunning = false;
                    clearInterval(this.intervalId);
                    this.intervalId = null;
                }
            }, 1000);
        },
        init() {
             // Call the backend method to get initial remaining seconds
             let initialSeconds = this.initialCooldown;
             console.log('Initial cooldown from entangled property:', initialSeconds);
             if (initialSeconds > 0) {
                 this.startTimer(initialSeconds);
             }
        }
    }"
        x-on:start-otp-resend-timer.window="startTimer($event.detail.duration)"
        x-init="init()"
>
    <button
            type="button"
            wire:click="handleResendOtp"
            x-bind:disabled="timerRunning"
            @class([
                'fi-btn fi-btn-size-md fi-btn-color-gray fi-ac-action fi-ac-btn-action',
            ])
            wire:loading.attr="disabled"
            wire:target="handleResendOtp"
    >
        {{-- Conditional Label --}}
        <template x-if="!timerRunning">
            <span>{{ $action->getLabel() }}</span>
        </template>
        <template x-if="timerRunning">
            <span>{{ __('filament-otp-auth::filament-otp-auth.buttons.resend_otp_timer', ['seconds' => '']) }}<span x-text="secondsRemaining"></span>s</span>
        </template>
    </button>
</div>
