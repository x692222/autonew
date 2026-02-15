<?php

namespace App\Notifications\System;

use App\Models\System\SystemRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemRequestSubmittedNotification extends Notification
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
        $request = $this->systemRequest->loadMissing(['user:id,firstname,lastname,email', 'dealerUser:id,firstname,lastname,email,dealer_id', 'dealerUser.dealer:id,name']);

        $fromGuard = $request->user_id ? 'backoffice' : ($request->dealer_user_id ? 'dealer' : 'unknown');
        $fromName = $request->user_id
            ? trim((string) optional($request->user)->firstname . ' ' . optional($request->user)->lastname)
            : trim((string) optional($request->dealerUser)->firstname . ' ' . optional($request->dealerUser)->lastname);
        $fromEmail = $request->user_id ? optional($request->user)->email : optional($request->dealerUser)->email;
        $dealerName = optional(optional($request->dealerUser)->dealer)->name;

        return (new MailMessage())
            ->subject('New System Request Submitted')
            ->line('A new system request has been submitted.')
            ->line('Status: ' . (string) $request->status?->value)
            ->line('Type: ' . (string) $request->type)
            ->line('Subject: ' . (string) $request->subject)
            ->line('From Guard: ' . $fromGuard)
            ->line('From Name: ' . ($fromName ?: '-'))
            ->line('From Email: ' . ($fromEmail ?: '-'))
            ->line('Dealer: ' . ($dealerName ?: '-'))
            ->line('Message:')
            ->line((string) $request->message);
    }
}

