<?php

namespace App\Support\Resolvers\System;

use Illuminate\Http\Request;

class SafeReturnToResolver
{
    public function resolve(Request $request, string $fallbackRouteName, array $fallbackRouteParameters = []): string
    {
        $returnTo = $request->input('return_to');

        if (is_string($returnTo) && $returnTo !== '' && str_starts_with($returnTo, '/')) {
            return $returnTo;
        }

        return route($fallbackRouteName, $fallbackRouteParameters);
    }
}
