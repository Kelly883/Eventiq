<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * SPA-aware password reset notification.
 *
 * The framework default builds its reset link with route('password.reset'),
 * which does not exist in this API-only backend (there is no Blade view to
 * point at), so an unhandled link always 500s. Instead, link straight into
 * the SPA's /reset-password page, which reads `token` and `email` from the
 * URL query and drives the resetPassword request itself.
 */
class ResetPassword extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost'), '/');
        $url = $frontendUrl
            . '/reset-password?token=' . $this->token
            . '&email=' . rawurlencode($notifiable->getEmailForPasswordReset());

        $expire = (int) config('auth.passwords.' . config('auth.defaults.passwords', 'users') . '.expire', 60);

        return (new MailMessage)
            ->subject('Reset Password Notification')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $url)
            ->line('This password reset link will expire in ' . $expire . ' minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}