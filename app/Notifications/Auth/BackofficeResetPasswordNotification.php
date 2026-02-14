<?php


namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class BackofficeResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable)
    {
        $resetUrl = url(route('backoffice.auth.password.reset', [
            'token' => $this->token,
        ], false));

        // include email in query string
        $resetUrl .= '?email=' . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Reset Password')
            ->line('Click the button below to reset your password.')
            ->action('Reset Password', $resetUrl)
            ->line('If you did not request a password reset, no further action is required.');
    }
}
