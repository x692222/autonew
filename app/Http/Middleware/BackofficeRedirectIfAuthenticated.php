<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BackofficeRedirectIfAuthenticated extends RedirectIfAuthenticated
{
  /**
   * @param Request $request
   * @param Closure $next
   * @param ...$guards
   * @return Response
   */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        if (Auth::guard('backoffice')->check()) {
            return redirect()->route('backoffice.index');
        }

        if (Auth::guard('dealer')->check()) {
            return redirect()->route('backoffice.dealer-configuration.stock.index');
        }

        $guards = empty($guards) ? [null] : $guards;
        foreach ($guards as $guard) {
            if ($guard !== null && Auth::guard($guard)->check()) {
                return redirect($this->redirectTo($request));
            }
        }

        return $next($request);
    }
}
