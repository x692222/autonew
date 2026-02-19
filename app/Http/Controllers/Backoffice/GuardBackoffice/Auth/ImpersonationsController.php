<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\Auth;
use App\Actions\Backoffice\GuardBackoffice\Backoffice\Impersonation\StartDealerUserImpersonationAction;
use App\Actions\Backoffice\GuardBackoffice\Backoffice\Impersonation\StopDealerUserImpersonationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\Auth\Impersonations\StartImpersonationsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\Auth\Impersonations\StopImpersonationsRequest;
use App\Models\Dealer\DealerUser;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ImpersonationsController extends Controller
{
    public function start(
        StartImpersonationsRequest $request,
        StartDealerUserImpersonationAction $action
    ): RedirectResponse {
        $email = (string) $request->validated('email');

        $target = DealerUser::query()
            ->with('dealer:id,is_active')
            ->where('email', $email)
            ->firstOrFail();

        if (!$target->is_active) {
            return back()->with('error', 'The user you are trying to impersonate is inactive.');
        }

        if (!$target->dealer || !$target->dealer->is_active) {
            return back()->with('error', 'The dealer of this user is inactive.');
        }

        $actor = $request->user('backoffice');

        $action->execute($actor, $target);

        return redirect()->route('backoffice.index')->with('success', 'Impersonation started.');
    }

    public function stop(
        StopImpersonationsRequest $request,
        StopDealerUserImpersonationAction $action
    ): RedirectResponse {
        $action->execute();

        return redirect()->route('backoffice.index')->with('success', 'Impersonation stopped.');
    }
}
