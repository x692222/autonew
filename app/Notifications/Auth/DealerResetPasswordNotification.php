<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class DealerResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable)
    {
        $resetUrl = url(route('backoffice.auth.dealer-password.reset', [
            'token' => $this->token,
        ], false));

        $resetUrl .= '?email=' . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Set Your Password')
            ->line('Your dealer account has been created.')
            ->line('Click the button below to set your password.')
            ->action('Set Password', $resetUrl)
            ->line('If you were not expecting this email, no further action is required.');
    }
}
