<?php

namespace App\Actions\Backoffice\Impersonation;

use App\Models\Dealer\DealerUser;
use App\Models\System\User;
use Illuminate\Support\Facades\Auth;

class StartDealerUserImpersonationAction
{
    public function execute(User $backofficeUser, DealerUser $dealerUser): void
    {
        session()->put('impersonation.active', true);
        session()->put('impersonation.backoffice_user_id', (string) $backofficeUser->getKey());
        session()->put('impersonation.dealer_user_id', (string) $dealerUser->getKey());
        session()->put('impersonation.started_at', now()->toDateTimeString());

        Auth::guard('dealer')->login($dealerUser);
    }
}
