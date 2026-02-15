<?php

namespace App\Notifications\System;

use App\Models\System\SystemRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemRequestStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly SystemRequest $systemRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Your System Request Status Was Updated')
            ->line('Your system request has been updated.')
            ->line('Subject: ' . (string) $this->systemRequest->subject)
            ->line('Status: ' . (string) $this->systemRequest->status?->value)
            ->when(
                ! empty($this->systemRequest->response),
                fn (MailMessage $mail) => $mail->line('Response: ' . (string) $this->systemRequest->response)
            );
    }
}

