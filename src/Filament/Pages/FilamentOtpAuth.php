<?php

declare(strict_types=1);

namespace SaeidSharafi\FilamentOtpAuth\Filament\Pages;

use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use SaeidSharafi\FilamentOtpAuth\Concerns\InteractsWithOtp;
use SaeidSharafi\FilamentOtpAuth\Exceptions\OtpException;
use Filament\Forms\Components\Section;
use Exception;

class FilamentOtpAuth extends SimplePage implements HasForms
{
    use InteractsWithOtp;
    use WithRateLimiting;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static string $view            = 'filament-otp-auth::pages.filament-otp-auth';

    public array $data = [];

    #[Url(history: true, keep: true, except: '')] // Keep step in URL, don't keep identifier input value
    public string $step         = 'identifier';
    public string $submitMethod = 'handleIdentifierSubmission';

    #[Url(history: true, keep: true)]
    public ?string $identifierValue = null; // Store the validated identifier between steps

    protected ?string $heading    = '';
    protected ?string $subheading = '';

    public int $initialResendCooldownSeconds = 0; // For timer component

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getHomeUrl());
            return;
        }

        $validSteps = ['identifier', 'password', 'otp', 'forgot_password_request', 'forgot_password_otp', 'reset_password'];
        if ( ! in_array($this->step, $validSteps)) {
            $this->goToStep('identifier', true); // Reset to initial step if URL step is invalid
            return;
        }

        // If navigating back/forward, ensure identifierValue is consistent with step
        if ( ! in_array($this->step, ['identifier', 'forgot_password_request']) && empty($this->identifierValue)) {
            // If we are on a step requiring an identifier, but it's missing, go back
            $this->goToStep('identifier', true);
            return;
        }
        if (in_array($this->step, ['identifier', 'forgot_password_request']) && ! empty($this->identifierValue)) {
            // If we land on an identifier entry step but already have one in URL, clear it maybe?
            // Or potentially pre-fill the form? Let's clear URL value for cleaner start.
            // $this->identifierValue = null; // Causes redirect loop if mount() is re-run by goToStep
            // Best handled by clearing form data instead
        }

        $this->submitMethod = match($this->step) {
            'password'                => 'authenticateWithPassword',
            'otp'                     => 'authenticateWithOtp',
            'forgot_password_request' => 'handleForgotPasswordRequest',
            'forgot_password_otp'     => 'handleForgotPasswordOtpVerification',
            'reset_password'          => 'handlePasswordReset',
            default                   => 'handleIdentifierSubmission', // 'identifier' step
        };

        $this->updateHeadingsAndSubheading();

        // Reset form data on mount unless specifically needed
        $formData = [];
        $this->form->fill($formData);

        match($this->step) {
            'password'            => $this->dispatch('focus-input', 'data.password'),
            'otp'                 => $this->dispatch('focus-input', 'data.otp'),
            'forgot_password_otp' => $this->dispatch('focus-input', 'data.otp'),
            'reset_password'      => $this->dispatch('focus-input', 'data.new_password'),
            default               => $this->dispatch('focus-input', 'data.identifier'), // Focus identifier on relevant steps
        };

        if (in_array($this->step, ['otp', 'forgot_password_otp']) && ! empty($this->identifierValue)) {
            $this->dispatchStartTimerEvent();
        }
    }

    protected function updateHeadingsAndSubheading(): void
    {
        switch ($this->step) {
            case 'password':
                $this->heading    = __('filament-otp-auth::filament-otp-auth.heading_enter_password');
                $this->subheading = __('filament-otp-auth::filament-otp-auth.subheading_entering_password', ['identifier' => $this->identifierValue]);
                break;
            case 'otp':
                $this->heading    = __('filament-otp-auth::filament-otp-auth.heading_enter_otp');
                $this->subheading = __('filament-otp-auth::filament-otp-auth.subheading_otp_sent', ['identifier' => $this->identifierValue]);
                break;
            case 'forgot_password_request':
                $this->heading    = __('filament-otp-auth::filament-otp-auth.heading_forgot_password');
                $this->subheading = __('filament-otp-auth::filament-otp-auth.subheading_forgot_password');
                break;
            case 'forgot_password_otp':
                $this->heading    = __('filament-otp-auth::filament-otp-auth.heading_forgot_password_otp');
                $this->subheading = __('filament-otp-auth::filament-otp-auth.subheading_forgot_password_otp_sent', ['identifier' => $this->identifierValue]);
                break;
            case 'reset_password':
                $this->heading    = __('filament-otp-auth::filament-otp-auth.heading_reset_password');
                $this->subheading = __('filament-otp-auth::filament-otp-auth.subheading_reset_password', ['identifier' => $this->identifierValue]);
                break;
            case 'identifier':
            default:
                $this->heading    = __('filament-otp-auth::filament-otp-auth.heading');
                $this->subheading = '';
                break;
        }
    }

    public function form(Form $form): Form
    {
        $this->updateHeadingsAndSubheading();

        $schema = [
            TextInput::make('identifier')
                ->label(__('filament-otp-auth::filament-otp-auth.form.identifier.label'))
                ->placeholder(__('filament-otp-auth::filament-otp-auth.form.identifier.placeholder'))
                ->required(fn () => in_array($this->step, ['identifier', 'forgot_password_request']))
                ->validationAttribute(__('filament-otp-auth::filament-otp-auth.form.identifier.validation_attribute'))
                ->extraInputAttributes(['autocomplete' => 'username webauthn'])
                ->autofocus(fn () => in_array($this->step, ['identifier', 'forgot_password_request'])) // Autofocus here
                ->hidden(fn () => ! in_array($this->step, ['identifier', 'forgot_password_request']))
                ->key('identifier-input-field'),

            TextInput::make('password')
                ->label(__('filament-panels::pages/auth/login.form.password.label'))
                ->password()
                ->required(fn () => 'password' === $this->step)
                ->hidden(fn () => 'password' !== $this->step)
                ->helperText(function (): ?\Illuminate\Support\HtmlString {
                    if ('password' !== $this->step) {
                        return null;
                    }
                    $message = __('filament-otp-auth::filament-otp-auth.buttons.forgot_password_link_text');
                    return new \Illuminate\Support\HtmlString(
                        '<a href="#" wire:click.prevent="goToForgotPasswordRequest" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-500 dark:hover:text-primary-400">'.$message.'</a>'
                    );
                })
                ->key('password-input-field'),

            TextInput::make('otp')
                ->label(__('filament-otp-auth::filament-otp-auth.form.otp.label'))
                ->placeholder(__('filament-otp-auth::filament-otp-auth.form.otp.placeholder'))
                ->required(fn () => in_array($this->step, ['otp', 'forgot_password_otp']))
                ->numeric()
                ->length(config('filament-otp-auth.otp_length', 6))
                ->validationAttribute(__('filament-otp-auth::filament-otp-auth.form.otp.validation_attribute'))
                ->helperText(__('filament-otp-auth::filament-otp-auth.form.otp.helper_text'))
                ->extraInputAttributes(['inputmode' => 'numeric', 'autocomplete' => 'one-time-code'])
                ->hidden(fn () => ! in_array($this->step, ['otp', 'forgot_password_otp']))
                ->key('otp-input-field'),

            Section::make(__('filament-otp-auth::filament-otp-auth.form.reset_password_section_heading'))
                ->hidden(fn () => 'reset_password' !== $this->step)
                ->schema([
                    TextInput::make('new_password')
                        ->label(__('filament-otp-auth::filament-otp-auth.form.new_password.label'))
                        ->password()
                        ->required(fn () => 'reset_password' === $this->step)
                        ->rule(Password::default())
                        ->autocomplete('new-password')
                        ->key('new-password-field'),
                    TextInput::make('new_password_confirmation')
                        ->label(__('filament-otp-auth::filament-otp-auth.form.new_password_confirmation.label'))
                        ->password()
                        ->required(fn () => 'reset_password' === $this->step)
                        ->same('data.new_password')
                        ->autocomplete('new-password')
                        ->key('new-password-confirmation-field'),
                ])->key('reset-password-section'),
        ];

        return $form
            ->schema($schema)
            ->statePath('data');
    }

    protected function getIdentifierFormAction(): Action
    {
        return Action::make('submitIdentifier')
            ->label(__('filament-otp-auth::filament-otp-auth.buttons.submit_identifier'))
            ->submit('handleIdentifierSubmission');
    }

    protected function getPasswordFormAction(): Action
    {
        return Action::make('submitPassword')
            ->label(__('filament-panels::pages/auth/login.form.actions.authenticate.label'))
            ->submit('authenticateWithPassword');
    }

    protected function getOtpFormAction(): Action
    {
        $submitMethod = match($this->step) {
            'forgot_password_otp' => 'handleForgotPasswordOtpVerification',
            default               => 'authenticateWithOtp',
        };
        return Action::make('submitOtp')
            ->label(__('filament-otp-auth::filament-otp-auth.buttons.submit_otp'))
            ->submit($submitMethod);
    }

    protected function getForgotPasswordRequestAction(): Action
    {
        return Action::make('requestPasswordReset')
            ->label(__('filament-otp-auth::filament-otp-auth.buttons.request_password_reset'))
            ->submit('handleForgotPasswordRequest');
    }

    protected function getResetPasswordAction(): Action
    {
        return Action::make('resetPassword')
            ->label(__('filament-otp-auth::filament-otp-auth.buttons.reset_password'))
            ->submit('handlePasswordReset');
    }

    protected function getResendOtpAction(): Action
    {
        $actionMethod = match ($this->step) {
            'forgot_password_otp' => 'handleResendPasswordResetOtp',
            default               => 'handleResendOtp', // Corresponds to 'otp' step
        };

        return Action::make('resendOtp')
            ->label(__('filament-otp-auth::filament-otp-auth.buttons.resend_otp'))
            ->button()
            ->color('gray')
            ->action($actionMethod)
            ->extraAttributes(['wire:loading.attr' => 'disabled'])
            ->visible(fn () => in_array($this->step, ['otp', 'forgot_password_otp']));
    }

    protected function getBackToLoginAction(): Action
    {
        return Action::make('backToLogin')
            ->label(__('filament-otp-auth::filament-otp-auth.buttons.back_to_login'))
            ->link()
            ->action(fn () => $this->goToStep('identifier', true))
            ->visible(fn () => in_array($this->step, ['password', 'forgot_password_request', 'forgot_password_otp', 'reset_password']));
    }

    protected function getFormActions(): array
    {
        $actions = [];
        switch ($this->step) {
            case 'password':
                $actions = [$this->getPasswordFormAction()];
                break;
            case 'otp':
            case 'forgot_password_otp':
                $actions = [
                    $this->getOtpFormAction(),
                    $this->getResendOtpAction(),
                ];
                break;
            case 'forgot_password_request':
                $actions = [$this->getForgotPasswordRequestAction()];
                break;
            case 'reset_password':
                $actions = [$this->getResetPasswordAction()];
                break;
            case 'identifier':
            default:
                $actions = [$this->getIdentifierFormAction()];
                break;
        }

        // Add BackToLogin if visible, ensuring it's not the primary action look
        if ($this->getBackToLoginAction()->isVisible()) {
            $actions[] = $this->getBackToLoginAction();
        }
        return $actions;
    }

    public function goToForgotPasswordRequest(): void
    {
        $this->goToStep('forgot_password_request');
    }

    public function handleIdentifierSubmission(): void
    {
        try {
            $this->rateLimit(5, 'otp-login-identifier');
        } catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $exception) {
            Notification::make()->danger()
                ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                    'seconds' => $exception->getHeaders()['Retry-After'],
                    'minutes' => ceil($exception->getHeaders()['Retry-After'] / 60),
                ]))
                ->body(
                    array_key_exists('minutes', $exception->getHeaders()) ? // Check if 'minutes' key exists before accessing
                    __('filament-panels::pages/auth/login.notifications.throttled.body.minutes', ['minutes' => ceil($exception->getHeaders()['Retry-After'] / 60)]) :
                    __('filament-panels::pages/auth/login.notifications.throttled.body.seconds', ['seconds' => $exception->getHeaders()['Retry-After']])
                )
                ->send();
            return;
        }

        try {
            $validatedData = $this->validate(['data.identifier' => ['required']]);
            $identifier    = data_get($validatedData, 'data.identifier');

            if ( ! $identifier) {
                throw ValidationException::withMessages(['data.identifier' => __('filament-otp-auth::filament-otp-auth.exceptions.unexpected_error')]);
            }

            $identifierType   = $this->getIdentifierType($identifier);
            $identifierColumn = $this->getIdentifierColumn($identifierType);
            if ( ! $identifierColumn) {
                throw ValidationException::withMessages(['data.identifier' => __('filament-otp-auth::filament-otp-auth.validation.invalid_identifier_format')]);
            }

            $authenticatableModel = $this->getAuthenticatableModel();
            $user                 = $authenticatableModel::where($identifierColumn, $identifier)->first();

            $this->identifierValue = $identifier; // Store validated identifier

            if ($user) {
                $passwordColumn = 'password'; // Assume default password column name
                $hasPassword    = ! empty($user->{$passwordColumn}) && ! Hash::check('', $user->{$passwordColumn}); // Check if password exists and is not just an empty hash

                if ($hasPassword) {
                    $this->goToStep('password');
                } else {
                    $this->sendOtpAndTransitionToOtpStep($identifier, $user);
                }
            } else {
                $createUser = config('filament-otp-auth.create_user_if_not_exists', true);
                if ($createUser) {
                    $this->sendOtpAndTransitionToOtpStep($identifier, null); // Pass null user for creation
                } else {
                    throw ValidationException::withMessages(['data.identifier' => __('filament-otp-auth::filament-otp-auth.exceptions.user_not_found')]);
                }
            }

            // $this->data['identifier'] = ''; // Clear input field - handled by goToStep/reset('data') now

        } catch (ValidationException $e) {
            $this->identifierValue = null; // Clear stored identifier on validation failure
            throw $e;
        } catch (Exception $e) {
            $this->identifierValue = null; // Clear stored identifier on general failure
            Log::error("Identifier Submission failed: ".$e->getMessage());
            Notification::make()->danger()->title(__('filament-otp-auth::filament-otp-auth.exceptions.unexpected_error'))->send();
        }
    }

    protected function sendOtpAndTransitionToOtpStep(string $identifier, ?Authenticatable $user): void
    {
        try {
            $this->generateAndSendOtp($identifier, $user); // Pass user object if found
            $this->identifierValue = $identifier; // Ensure identifierValue is set
            $this->goToStep('otp');
        } catch (OtpException | Exception $e) {
            // Don't transition if sending failed
            $this->identifierValue = null; // Clear potentially stored value
            $this->goToStep('identifier'); // Stay on or return to identifier step
            Notification::make()->danger()
                ->title($e instanceof OtpException ? $e->getMessage() : __('filament-otp-auth::filament-otp-auth.exceptions.unexpected_error'))
                ->send();
        }
    }

    public function authenticateWithPassword(): void
    {
        if ('password' !== $this->step || empty($this->identifierValue)) {
            $this->goToStep('identifier', true);
            return;
        }

        try {
            $this->rateLimit(5, 'otp-login-password');
        } catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $exception) {
            Notification::make()->danger()
                ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [/* ... throttled params ... */]))
                ->send();
            return;
        }

        try {
            $validatedData = $this->validate(['data.password' => 'required']);
            $password      = data_get($validatedData, 'data.password');

            $identifierType   = $this->getIdentifierType($this->identifierValue);
            $identifierColumn = $this->getIdentifierColumn($identifierType);
            $credentials      = [
                $identifierColumn => $this->identifierValue,
                'password'        => $password,
            ];

            if ( ! Filament::auth()->attempt($credentials, true /* remember */)) {
                throw ValidationException::withMessages(['data.password' => __('filament-panels::pages/auth/login.messages.failed')]);
            }

            session()->regenerate();
            $this->redirect(Filament::getHomeUrl(), navigate: true);

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("Password Authentication failed: ".$e->getMessage());
            Notification::make()->danger()->title(__('filament-otp-auth::filament-otp-auth.exceptions.unexpected_error'))->send();
        }
    }

    public function authenticateWithOtp(): void
    {
        if ('otp' !== $this->step || empty($this->identifierValue)) {
            $this->goToStep('identifier', true);
            return;
        }

        try {
            $this->rateLimit(5, 'otp-login-verify');
        } catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $exception) {
            Notification::make()->danger()
                ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [/* ... throttled params ... */]))
                ->send();
            return;
        }

        try {
            $otpLength     = config('filament-otp-auth.otp_length', 6);
            $validatedData = $this->validate(['data.otp' => ['required', 'numeric', "digits:{$otpLength}"]]);
            $otpCode       = data_get($validatedData, 'data.otp');

            if ($this->verifyOtp($this->identifierValue, $otpCode)) {
                $identifierType       = $this->getIdentifierType($this->identifierValue);
                $identifierColumn     = $this->getIdentifierColumn($identifierType);
                $authenticatableModel = $this->getAuthenticatableModel();
                $user                 = $authenticatableModel::where($identifierColumn, $this->identifierValue)->first();

                if ($user) {
                    Filament::auth()->login($user, true /* remember */);
                    session()->regenerate();
                    $this->redirect(Filament::getHomeUrl(), navigate: true);
                } else {
                    // Should not happen if OTP verification implies user exists/was created
                    Log::error("User not found after successful OTP verification for {$this->identifierValue}");
                    throw ValidationException::withMessages(['data.otp' => __('filament-otp-auth::filament-otp-auth.exceptions.unexpected_error')]);
                }
            } else {
                throw ValidationException::withMessages(['data.otp' => __('filament-otp-auth::filament-otp-auth.exceptions.invalid_otp')]);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("OTP Authentication failed: ".$e->getMessage());
            Notification::make()->danger()->title(__('filament-otp-auth::filament-otp-auth.exceptions.unexpected_error'))->send();
        }
    }

    public function handleForgotPasswordRequest(): void
    {
        try {
            $this->rateLimit(5, 'otp-forgot-password-request');
        } catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $exception) {
            Notification::make()->danger()->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [/* ... */]))->send();
            return;
        }

        try {
            $validatedData = $this->validate(['data.identifier' => ['required']]);
            $identifier    = data_get($validatedData, 'data.identifier');

            $identifierType   = $this->getIdentifierType($identifier);
            $identifierColumn = $this->getIdentifierColumn($identifierType);
            if ( ! $identifierColumn) {
                throw ValidationException::withMessages(['data.identifier' => __('filament-otp-auth::filament-otp-auth.validation.invalid_identifier_format')]);
            }

            $authenticatableModel = $this->getAuthenticatableModel();
            $user                 = $authenticatableModel::where($identifierColumn, $identifier)->first();

            if ( ! $user) {
                Notification::make()->success()
                    ->title(__('filament-otp-auth::notifications.password_reset_link_sent_if_exists'))
                    ->send();
                $this->goToStep('identifier', true); // Go back to login start discreetly
                return;
            }

            $this->generateAndSendPasswordResetOtp($identifier, $user); // Use NEW trait method

            $this->identifierValue = $identifier; // Store identifier
            $this->goToStep('forgot_password_otp');

        } catch (ValidationException $e) {
            throw $e;
        } catch (OtpException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        } catch (Exception $e) {
            Log::error("Forgot Password Request failed for {$identifier}: ".$e->getMessage());
            Notification::make()->danger()->title(__('filament-otp-auth::exceptions.unexpected_error'))->send();
        }
    }

    public function handleForgotPasswordOtpVerification(): void
    {
        if ('forgot_password_otp' !== $this->step || empty($this->identifierValue)) {
            $this->goToStep('identifier', true);
            return;
        }

        try {
            $this->rateLimit(5, 'otp-forgot-password-verify');
        } catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $exception) {
            Notification::make()->danger()->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [/* ... */]))->send();
            return;
        }

        try {
            $otpLength     = config('filament-otp-auth.otp_length', 6);
            $validatedData = $this->validate(['data.otp' => ['required', 'numeric', "digits:{$otpLength}"]]);
            $otpCode       = data_get($validatedData, 'data.otp');

            if ($this->verifyPasswordResetOtp($this->identifierValue, $otpCode)) {
                $this->goToStep('reset_password');
            } else {
                throw ValidationException::withMessages(['data.otp' => __('filament-otp-auth::filament-otp-auth.exceptions.invalid_otp')]);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("Forgot Password OTP verification failed for {$this->identifierValue}: ".$e->getMessage());
            Notification::make()->danger()->title(__('filament-otp-auth::exceptions.unexpected_error'))->send();
        }
    }

    public function handlePasswordReset(): void
    {
        if ('reset_password' !== $this->step || empty($this->identifierValue)) {
            $this->goToStep('identifier', true);
            return;
        }

        try {
            $this->rateLimit(5, 'otp-reset-password-submit');
        } catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $exception) {
            Notification::make()->danger()->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [/* ... */]))->send();
            return;
        }

        try {
            $validatedData = $this->validate([
                'data.new_password'              => ['required', Password::default(), 'confirmed'],
                'data.new_password_confirmation' => ['required'],
            ]);

            $newPassword = data_get($validatedData, 'data.new_password');

            $identifierType       = $this->getIdentifierType($this->identifierValue);
            $identifierColumn     = $this->getIdentifierColumn($identifierType);
            $authenticatableModel = $this->getAuthenticatableModel();
            /** @var Authenticatable|null $user */
            $user = $authenticatableModel::where($identifierColumn, $this->identifierValue)->first();

            if ( ! $user) {
                Log::error("User not found during password reset for {$this->identifierValue}");
                Notification::make()->danger()->title(__('filament-otp-auth::exceptions.user_not_found'))->send();
                $this->goToStep('identifier', true);
                return;
            }

            $user->forceFill([
                'password' => Hash::make($newPassword),
                // 'remember_token' => Str::random(60), // Optional: Invalidate other sessions
            ])->save();

            Notification::make()->success()
                ->title(__('filament-otp-auth::notifications.password_reset_success'))
                ->send();

            $this->goToStep('identifier', true);

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("Password reset failed for {$this->identifierValue}: ".$e->getMessage());
            Notification::make()->danger()->title(__('filament-otp-auth::exceptions.unexpected_error'))->send();
        }
    }

    public function handleResendOtp(): void
    {
        if ('otp' !== $this->step || empty($this->identifierValue)) {
            return; // Only for 'otp' (login/registration) step
        }
        try {
            $identifierType       = $this->getIdentifierType($this->identifierValue);
            $identifierColumn     = $this->getIdentifierColumn($identifierType);
            $authenticatableModel = $this->getAuthenticatableModel();
            $user                 = $authenticatableModel::where($identifierColumn, $this->identifierValue)->first();

            // Use the original method for login/registration OTP
            // Pass user object if found, allows generation even if just created
            $this->generateAndSendOtp($this->identifierValue, $user, resend: true);

            Notification::make()->success()
                ->title(__('filament-otp-auth::notifications.otp_resent_success'))->send();
            $this->dispatchStartTimerEvent();
        } catch (OtpException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        } catch (Exception $e) {
            Log::error("Resend Login OTP failed for {$this->identifierValue}: ".$e->getMessage());
            Notification::make()->danger()
                ->title(__('filament-otp-auth::exceptions.unexpected_error'))->send();
        }
    }

    public function handleResendPasswordResetOtp(): void
    {
        if ('forgot_password_otp' !== $this->step || empty($this->identifierValue)) {
            return;
        }

        try {
            $identifierType       = $this->getIdentifierType($this->identifierValue);
            $identifierColumn     = $this->getIdentifierColumn($identifierType);
            $authenticatableModel = $this->getAuthenticatableModel();
            $user                 = $authenticatableModel::where($identifierColumn, $this->identifierValue)->first();

            if ( ! $user) {
                Log::warning("User not found for resending password reset OTP: {$this->identifierValue}");
                Notification::make()->warning()->title(__('filament-otp-auth::exceptions.user_not_found'))->send();
                return;
            }

            // Use the specific password reset OTP generation method
            $this->generateAndSendPasswordResetOtp($this->identifierValue, $user, resend: true);

            Notification::make()->success()
                ->title(__('filament-otp-auth::notifications.otp_resent_success'))->send();
            $this->dispatchStartTimerEvent(); // Restart timer

        } catch (OtpException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        } catch (Exception $e) {
            Log::error("Resend Password Reset OTP failed for {$this->identifierValue}: ".$e->getMessage());
            Notification::make()->danger()
                ->title(__('filament-otp-auth::exceptions.unexpected_error'))->send();
        }
    }

    protected function dispatchStartTimerEvent(): void
    {
        $this->initialResendCooldownSeconds = $this->calculateRemainingCooldown();
        if ($this->initialResendCooldownSeconds > 0) {
            $this->dispatch('start-otp-resend-timer', duration: $this->initialResendCooldownSeconds);
        } else {
            // Ensure timer component is reset if cooldown is 0
            $this->dispatch('reset-otp-resend-timer');
        }
    }

    public function calculateRemainingCooldown(): int
    {
        if ( ! in_array($this->step, ['otp', 'forgot_password_otp']) || empty($this->identifierValue)) {
            return 0;
        }

        $lastSentAt = $this->getLastOtpSentAt($this->identifierValue);

        if ( ! $lastSentAt instanceof Carbon) {
            return 0; // No record or not a Carbon instance
        }

        $cooldownSeconds = (int) config('filament-otp-auth.otp_resend_delay_seconds', 60);
        if ($cooldownSeconds <= 0) {
            return 0;
        }

        $cooldownExpiresAt = $lastSentAt->copy()->addSeconds($cooldownSeconds);
        $now               = now();

        // Check if the expiration time is in the past
        if ($now->greaterThanOrEqualTo($cooldownExpiresAt)) {
            return 0;
        }

        // Calculate remaining seconds (will be positive or zero)
        $remainingSeconds = $now->diffInSeconds($cooldownExpiresAt, false); // false = don't get absolute value

        return max(0, $remainingSeconds); // Ensure it's not negative due to slight timing issues
    }

    protected function goToStep(string $targetStep, bool $clearIdentifier = false): void
    {
        // Update state *before* potential redirect/refresh triggered by URL change
        $this->step = $targetStep;

        if ($clearIdentifier) {
            $this->identifierValue = null;
        }

        // Reset form data and validation for the new step
        $this->reset('data');
        $this->clearValidation();

        // Re-run mount logic to correctly set up the new step's state
        // This handles headings, submit methods, focus, timer dispatch etc.
        // Livewire's #[Url] attribute handles the URL update automatically.
        $this->mount();
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-otp-auth::filament-otp-auth.title');
    }

    public function getHeading(): string|Htmlable
    {
        return $this->heading ?: __('filament-otp-auth::filament-otp-auth.heading');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->subheading;
    }

    public function hasLogo(): bool
    {
        // Check Filament configuration for logo visibility if needed
        // return config('filament.brand') !== null;
        return true; // Keep simple for now
    }

    protected function getActions(): array
    {
        // SimplePage typically doesn't have header/footer actions in this context
        return [];
    }
}
