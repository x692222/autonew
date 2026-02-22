<?php

namespace App\Http\Middleware;

use App\Support\Security\GuardIpBlockService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockBlockedIpMiddleware
{
    public function __construct(private readonly GuardIpBlockService $ipBlockService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->ipBlockService->isIpBlocked($request->ip())) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access from this IP address is blocked.',
                ], 403);
            }

            abort(403, 'Access from this IP address is blocked.');
        }

        return $next($request);
    }
}
