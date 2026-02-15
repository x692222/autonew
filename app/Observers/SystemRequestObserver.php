<?php

namespace App\Observers;

use App\Models\System\SystemRequest;
use App\Notifications\System\SystemRequestSubmittedNotification;
use Illuminate\Support\Facades\Notification;

class SystemRequestObserver
{
    public function created(SystemRequest $systemRequest): void
    {
        $email = config('system-requests.notify_email');

        if (empty($email)) {
            return;
        }

        Notification::route('mail', $email)->notify(new SystemRequestSubmittedNotification($systemRequest));
    }
}

