<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\Auth;

use App\Http\Controllers\Controller;
use App\Models\Dealer\DealerUser;
use App\Models\System\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ForgotPasswordController extends Controller
{
    public function create(Request $request)
    {
        return Inertia::render('GuardBackoffice/Auth/ForgotPassword', [
            'email' => (string) $request->query('email', ''),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:190'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $broker = $this->resolveBroker((string) $data['email']);

        $status = Password::broker($broker)->sendResetLink(['email' => $data['email']]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()->withErrors([
            'email' => __($status),
        ])->withInput();
    }

    private function resolveBroker(string $email): string
    {
        if (User::query()->where('email', $email)->exists()) {
            return 'users';
        }

        if (DealerUser::query()->where('email', $email)->exists()) {
            return 'dealers';
        }

        return 'users';
    }
}
