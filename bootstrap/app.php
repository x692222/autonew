<?php

use App\Http\Middleware\EnsureAjaxRequestMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/backoffice.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ])
            ->validateCsrfTokens(except: [
//                '*/site/paygate-result',
//                '*/site/paygate-webook',
            ]);
        $middleware->redirectGuestsTo(function (Request $request) {
//            return match (true) {
//                $request->is('backoffice/*') || $request->routeIs('backoffice.*')
//                => route('backoffice.auth.login.show'),
//
//                $request->is('dealer/*') || $request->routeIs('dealer.*')
//                => route('dealer.auth.login'),
//
//                default => null,
//            };
            return match (true) {
                $request->is('backoffice/*') || $request->routeIs('backoffice.*')
                => route('backoffice.auth.login.show'),

                $request->is('dealer/*') || $request->routeIs('dealer.*')
                => route('dealer.auth.login'),

                default => Route::has('login')
                    ? route('login')
                    : route('backoffice.auth.login.show'),
            };
        });

        $middleware->redirectUsersTo(function (Request $request) {
            return match (true) {
                $request->is('backoffice/*') || $request->routeIs('backoffice.*') => '/backoffice',
//                $request->is('dealer/*')     || $request->routeIs('dealer.*')     => '/dealer',
                default => '/',
            };
        });

        $middleware->alias([
            'ajax' => EnsureAjaxRequestMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->header('X-Inertia')) {
                return back()
                    ->withErrors($e->errors())
                    ->withInput($request->except(['password', 'password_confirmation']));
            }

            return null;
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            $guard = $e->guards()[0]??null;

            $loginRoute = match ($guard) {
                'dealer' => Route::has('dealer.auth.login') ? 'dealer.auth.login' : 'backoffice.auth.login.show',
                'backoffice' => 'backoffice.auth.login.show',
                default => Route::has('login') ? 'login' : 'backoffice.auth.login.show',
            };

            return redirect()->guest(route($loginRoute));
        });
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->expectsJson()) {
                // Read PHP limits
                $uploadMax = ini_get('upload_max_filesize');
                $postMax = ini_get('post_max_size');

                // Convert to MB (handles K/M/G)
                $toMb = function ($value) {
                    $unit = strtoupper(substr($value, -1));
                    $number = (float)$value;

                    return match ($unit) {
                        'G' => $number * 1024,
                        'K' => round($number / 1024, 2),
                        default => $number, // M or no unit
                    };
                };

                $maxMb = min($toMb($uploadMax), $toMb($postMax));

                return response()->json([
                    'message' => "Upload too large. Maximum allowed file size is {$maxMb}MB.",
                ], 413);
            }

            return null;
        });
    })
    ->create();
