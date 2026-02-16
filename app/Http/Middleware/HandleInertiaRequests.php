<?php

namespace App\Http\Middleware;

use App\Models\Dealer\DealerUser;
use App\Models\Stock\StockFeatureTag;
use App\Models\System\SystemRequest;
use App\Support\AbilityValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $guard = match (true) {
            auth('dealer')->check() => 'dealer',
            auth('backoffice')->check() => 'backoffice',
            default => 'web',
        };

        $basics = [
            'auth'  => function() use ($request, $guard) {
                if (stripos(Route::currentRouteName(), 'console.') !== false) {
                    return [];
                }

                return [
                    'guard'     => $guard,
//                    'abilities' => function() use ($request, $guard) {
//                        $actor = $request->user($guard);
//                        return app(AbilityResolver::class)->forUser($actor); // @todo singleton or cache?
//                    },
                    'user'      => auth()->guard($guard)->user() ? [
                        'id'        => auth()->guard($guard)->user()->id,
                        'firstname' => auth()->guard($guard)->user()->firstname,
                        'lastname'  => auth()->guard($guard)->user()->lastname,
                        'email'     => auth()->guard($guard)->user()->email,
                        'abilities' => (new AbilityValidator())->permissionsFor($request->user($guard), $guard),
                    ] : null,
                ];
            },
            'impersonation' => function () use ($request) {
                $isImpersonating = (bool) $request->session()->get('impersonation.active', false)
                    && auth('dealer')->check();

                $defaultEmail = null;
                if (app()->environment('local')) {
                    $defaultEmail = DealerUser::query()
                        ->where('is_active', true)
                        ->whereHas('dealer', fn ($query) => $query->where('is_active', true))
                        ->orderBy('email')
                        ->value('email');
                }

                return [
                    'active' => $isImpersonating,
                    'dealer_user_email' => auth('dealer')->user()?->email,
                    'backoffice_user_id' => $request->session()->get('impersonation.backoffice_user_id'),
                    'default_email' => $defaultEmail,
                ];
            },
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
            ],
            'pending_counts' => fn () => [
                'system_requests' => auth('backoffice')->check()
                    ? SystemRequest::query()->submitted()->count()
                    : 0,
                'feature_tags' => auth('backoffice')->check()
                    ? StockFeatureTag::query()->pendingReview()->count()
                    : 0,
            ],
            'server_now' => fn () => now()->toIso8601String(),
        ];

        return [
            ...$basics,
            ...parent::share($request),
            //
        ];
    }

    public function rootView(Request $request)
    {
        return match (true) {
            $request->is('backoffice/*') || $request->routeIs('backoffice.*')
            => 'backoffice',

            $request->is('dealer/*') || $request->routeIs('dealer.*')
            => 'dealer',

            default => 'website',
        };
    }
}
