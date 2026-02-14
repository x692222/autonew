<?php

namespace App\Actions\Backoffice\Impersonation;

use App\Models\System\User;
use Illuminate\Support\Facades\Auth;

class StopDealerUserImpersonationAction
{
    public function execute(): void
    {
        if (!Auth::guard('backoffice')->check()) {
            $id = (string) session('impersonation.backoffice_user_id', '');

            if ($id !== '') {
                $backofficeUser = User::query()->find($id);

                if ($backofficeUser) {
                    Auth::guard('backoffice')->login($backofficeUser);
                }
            }
        }

        if (Auth::guard('dealer')->check()) {
            Auth::guard('dealer')->logout();
        }

        session()->forget('impersonation');
    }
}
