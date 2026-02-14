<?php

namespace App\Http\Controllers\Backoffice\Auth;

use App\Actions\Backoffice\Auth\ResetDealerUserPasswordAction;
use App\Http\Controllers\Controller;
use App\Models\Dealer\DealerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DealerResetPasswordController extends Controller
{
    public function create(string $token, Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
            'submitRoute' => 'backoffice.auth.dealer-password.update',
        ]);
    }

    public function store(Request $request, ResetDealerUserPasswordAction $resetAction): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $status = Password::broker('dealers')->reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'token' => $data['token'],
            ],
            function (DealerUser $user) use ($data, $resetAction) {
                $resetAction->execute($user, $data['password']);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('backoffice.auth.login.show')
                ->with('status', __($status));
        }

        return back()->withErrors([
            'email' => __($status),
        ])->withInput();
    }
}
