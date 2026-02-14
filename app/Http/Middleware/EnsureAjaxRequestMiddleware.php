<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAjaxRequestMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /**
         * Accept:
         * - XHR (fetch / axios)
         * - Inertia requests
         */
        if (! $request->ajax() && ! $request->header('X-Inertia')) {
            abort(403, 'This endpoint only accepts AJAX requests.');
        }

        return $next($request);
    }
}
