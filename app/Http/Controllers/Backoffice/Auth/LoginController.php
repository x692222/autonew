<?php

namespace App\Http\Controllers\Backoffice\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:190'],
            'remember' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $remember = (bool)($data['remember'] ?? false);

        if (!Auth::guard('backoffice')->attempt(
            ['email' => $data['email'], 'password' => $data['password']],
            $remember
        )) {
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->withInput();
        }

        $request->session()->regenerate();

        return redirect()->route('backoffice.index');
    }

    public function destroy(Request $request)
    {
        Auth::guard('backoffice')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('backoffice.auth.login.show');
    }
}
