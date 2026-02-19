<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\Auth;
use App\Actions\Backoffice\GuardBackoffice\Backoffice\Auth\ResetBackofficeUserPasswordAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ResetPasswordController extends Controller
{
    public function create(string $token, Request $request)
    {
        return Inertia::render('GuardBackoffice/Auth/ResetPassword', [
            'token' => $token,
            'email' => (string)$request->query('email', ''),
        ]);
    }

    public function store(Request $request, ResetBackofficeUserPasswordAction $resetAction)
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

        // If you have a backoffice-specific broker, replace 'users' accordingly.
        $status = Password::broker('users')->reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'token' => $data['token'],
            ],
            function ($user) use ($data, $resetAction) {
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
