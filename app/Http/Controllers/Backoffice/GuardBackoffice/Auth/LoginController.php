<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackofficeLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function show()
    {
        return Inertia::render('GuardBackoffice/Auth/Login');
    }

    public function store(BackofficeLoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        return $this->redirectAfterLogin($request);
    }

    public function destroy(Request $request)
    {
        if (Auth::guard('backoffice')->check()) {
            Auth::guard('backoffice')->logout();
        }

        if (Auth::guard('dealer')->check()) {
            Auth::guard('dealer')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('backoffice.auth.login.show');
    }

    private function redirectAfterLogin(Request $request)
    {
        if (Auth::guard('backoffice')->check()) {
            return redirect()->intended(route('backoffice.index'));
        }

        if (Auth::guard('dealer')->check()) {
            return redirect()->intended(route('backoffice.dealer-configuration.stock.index'));
        }

        return redirect()->route('backoffice.auth.login.show');
    }
}
