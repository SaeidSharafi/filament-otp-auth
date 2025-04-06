<?php

declare(strict_types=1);

return [
    'title' => 'Login',
    'heading' => 'Sign in to your account',
    'heading_enter_password' => 'Enter Password',
    'subheading_entering_password' => 'Enter the password for :identifier',
    'heading_enter_otp' => 'Enter Authentication Code',
    'subheading_otp_sent' => 'An authentication code has been sent to :identifier',
    'heading_forgot_password' => 'Forgot Password',
    'subheading_forgot_password' => 'Enter your email or phone number to receive a password reset code.',
    'heading_forgot_password_otp' => 'Enter Password Reset Code',
    'subheading_forgot_password_otp_sent' => 'A password reset code has been sent to :identifier.',
    'heading_reset_password' => 'Set New Password',
    'subheading_reset_password' => 'Enter your new password below for :identifier.',

    'form' => [
        'identifier' => [
            'label' => 'Email or Phone Number',
            'placeholder' => 'your@email.com or +1234567890',
            'validation_attribute' => 'email or phone number',
        ],
        'otp' => [
            'label' => 'One-Time Password (OTP)',
            'placeholder' => 'Enter the code',
            'helper_text' => 'Enter the code sent to your email/phone.',
            'validation_attribute' => 'OTP code',
        ],
        'reset_password_section_heading' => 'New Password',
        'new_password' => [
            'label' => 'New Password',
        ],
        'new_password_confirmation' => [
            'label' => 'Confirm New Password',
        ],
    ],

    'buttons' => [
        'submit_identifier' => 'Continue',
        'submit_otp' => 'Verify Code',
        'resend_otp' => 'Resend Code',
        'resend_otp_timer' => 'Resend OTP in ',
        'forgot_password_link_text' => 'Forgot Password?',
        'request_password_reset' => 'Send Reset Code',
        'reset_password' => 'Set New Password',
        'back_to_login' => 'Back to Login',
    ],

    'notifications' => [
        'subject' => 'Your One-Time Password (OTP)',
        'greeting' => 'Hello :name,',
        'line' => 'Your login code is: **:otp**',
        'expiry' => 'This code will expire in :minutes minutes.',
        'warning' => 'If you did not request this code, please ignore this message.',
        'sms_line' => 'Your login code is: :otp. It expires in '.config('filament-otp-auth.otp_expiry_minutes', 5).' minutes.', // Example SMS format
        'otp_resent_success' => 'A new OTP code has been sent.',
        'throttled_verification' => 'Too many verification attempts. Please try again later.',
        'cooldown_active' => 'Please wait :seconds seconds before requesting another code.',
        'password_reset_subject' => 'Reset Your Password',
        'password_reset_line1' => 'You are receiving this email because we received a password reset request for your account.',
        'password_reset_line2' => 'Your password reset code is: :otp',
        'password_reset_line3' => 'This code will expire in :minutes minutes. If you did not request a password reset, no further action is required.',
        // 'password_reset_action_text' => 'Reset Password on :app_name', // Optional action button text
        // 'password_reset_salutation' => 'Regards', // Optional closing
        'password_reset_link_sent_if_exists' => 'If an account exists for the provided identifier, a password reset code has been sent.',
        'password_reset_success' => 'Your password has been successfully reset.',
    ],

    'exceptions' => [
        'too_many_attempts' => 'Too many attempts. Please try again in :seconds seconds.',
        'invalid_otp' => 'The OTP code provided is invalid or has expired.',
        'expired_otp' => 'The OTP code has expired.', // Keep for potential specific use
        'notification_failed' => 'Failed to send the OTP code. Please try again later.',
        'user_not_found' => 'No account found with this email or phone number.',
        'config_error' => 'Login configuration error: :details',
        'unexpected_error' => 'An unexpected error occurred. Please try again.',
    ],

    'validation' => [
        'invalid_identifier_format' => 'Please enter a valid email address or phone number.',
    ],
];
