<?php

use App\Http\Controllers\Backoffice\Auth\ForgotPasswordController;
use App\Http\Controllers\Backoffice\Auth\LoginController;
use App\Http\Controllers\Backoffice\Auth\ResetPasswordController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Middleware\BackofficeRedirectIfAuthenticated;
use Illuminate\Support\Facades\Route;

Route::group(['as' => 'backoffice.', 'prefix' => 'backoffice'], function () {
    Route::group(['as' => 'auth.'], function () {

            Route::group(['middleware' => 'guest:backoffice'], function () {
            Route::group(['as' => 'login.', 'middleware' => BackofficeRedirectIfAuthenticated::class], function () {
                Route::get('login', [LoginController::class, 'show'])->name('show');
                Route::post('login', [LoginController::class, 'store'])->name('store');
            });

            // password
            Route::group(['as' => 'password.'], function () {
                Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('request');
                Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('email');
                Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('reset');
                Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('update');
            });
        });
    });

    Route::group(['middleware' => 'auth:backoffice'], function () {
        Route::get('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/test', [DashboardController::class, 'test'])->name('test');
    });
});
