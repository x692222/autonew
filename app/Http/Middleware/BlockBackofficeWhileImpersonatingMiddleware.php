<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockBackofficeWhileImpersonatingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $isImpersonating = (bool) $request->session()->get('impersonation.active', false)
            && auth('dealer')->check()
            && auth('backoffice')->check();

        if (!$isImpersonating) {
            return $next($request);
        }

        if (
            $request->routeIs('backoffice.index')
            || $request->routeIs('backoffice.logout')
            || $request->routeIs('backoffice.auth.impersonations.stop')
        ) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Backoffice routes are unavailable while impersonating. Stop impersonation first.',
            ], 403);
        }

        return redirect()
            ->route('backoffice.dealer-configuration.branches.index')
            ->with('error', 'Backoffice routes are unavailable while impersonating. Stop impersonation first.');
    }
}
