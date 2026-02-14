<?php

use App\Http\Controllers\Backoffice\Auth\ForgotPasswordController;
use App\Http\Controllers\Backoffice\Auth\DealerResetPasswordController;
use App\Http\Controllers\Backoffice\Auth\ImpersonationsController;
use App\Http\Controllers\Backoffice\Auth\LoginController;
use App\Http\Controllers\Backoffice\Auth\ResetPasswordController;
use App\Http\Controllers\Backoffice\DealerManagement\DealersController;
use App\Http\Controllers\Backoffice\DealerManagement\Dealers\AuditLogsController;
use App\Http\Controllers\Backoffice\DealerManagement\Dealers\BillingsController;
use App\Http\Controllers\Backoffice\DealerManagement\Dealers\BranchesController;
use App\Http\Controllers\Backoffice\DealerManagement\Dealers\NotificationHistoriesController;
use App\Http\Controllers\Backoffice\DealerManagement\Dealers\NotesController;
use App\Http\Controllers\Backoffice\DealerManagement\Dealers\OverviewsController;
use App\Http\Controllers\Backoffice\DealerManagement\Dealers\SalesPeopleController;
use App\Http\Controllers\Backoffice\DealerManagement\Dealers\SettingsController;
use App\Http\Controllers\Backoffice\DealerManagement\Dealers\StockController;
use App\Http\Controllers\Backoffice\DealerManagement\Dealers\UsersController;
use App\Http\Controllers\Backoffice\System\AccessManagementController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\DealerConfiguration\BranchesController as DealerConfigurationBranchesController;
use App\Http\Controllers\Backoffice\DealerConfiguration\EditDealershipController as DealerConfigurationEditDealershipController;
use App\Http\Controllers\Backoffice\DealerConfiguration\SalesPeopleController as DealerConfigurationSalesPeopleController;
use App\Http\Controllers\Backoffice\DealerConfiguration\UsersController as DealerConfigurationUsersController;
use App\Http\Controllers\Backoffice\NotesController as BackofficeNotesController;
use App\Http\Controllers\Backoffice\System\LocationsManagementController;
use App\Http\Controllers\Backoffice\System\UsersManagementController;
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

            Route::group(['as' => 'dealer-password.'], function () {
                Route::get('/dealer/reset-password/{token}', [DealerResetPasswordController::class, 'create'])->name('reset');
                Route::post('/dealer/reset-password', [DealerResetPasswordController::class, 'store'])->name('update');
            });
        });
    });

    Route::group(['middleware' => 'auth:backoffice'], function () {
        Route::get('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/test', [DashboardController::class, 'test'])->name('test');
        Route::post('/auth/impersonations/start', [ImpersonationsController::class, 'start'])->name('auth.impersonations.start');
        Route::post('/auth/impersonations/stop', [ImpersonationsController::class, 'stop'])->name('auth.impersonations.stop');

        Route::prefix('notes')
            ->as('notes.')
            ->middleware('ajax')
            ->group(function () {
                Route::get('{noteableType}/{noteableId}', [BackofficeNotesController::class, 'index'])->name('index');
                Route::post('{noteableType}/{noteableId}', [BackofficeNotesController::class, 'store'])->name('store');
                Route::patch('{noteableType}/{noteableId}/{note}', [BackofficeNotesController::class, 'update'])->name('update');
                Route::delete('{noteableType}/{noteableId}/{note}', [BackofficeNotesController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('system/user-management')
            ->as('system.user-management.')
            ->group(function () {
                Route::resource('users', UsersManagementController::class)
                    ->except(['show']);

                Route::patch('users/{user}/activate', [UsersManagementController::class, 'activate'])
                    ->name('users.activate');
                Route::patch('users/{user}/deactivate', [UsersManagementController::class, 'deactivate'])
                    ->name('users.deactivate');
                Route::post('users/{user}/reset-password', [UsersManagementController::class, 'resetPassword'])->name('users.reset-password');
                Route::get('users/{user}/permissions', [AccessManagementController::class, 'edit'])->name('users.permissions.edit');
                Route::patch('users/{user}/permissions', [AccessManagementController::class, 'update'])->name('users.permissions.update');
            });

        Route::prefix('system/location-management')
            ->as('system.locations-management.')
            ->group(function () {
                Route::get('/', [LocationsManagementController::class, 'index'])->name('index');
                Route::get('{type}/create', [LocationsManagementController::class, 'create'])
                    ->whereIn('type', ['country', 'state', 'city', 'suburb'])
                    ->name('create');
                Route::post('{type}', [LocationsManagementController::class, 'store'])
                    ->whereIn('type', ['country', 'state', 'city', 'suburb'])
                    ->name('store');
                Route::get('{type}/{location}/edit', [LocationsManagementController::class, 'edit'])
                    ->whereIn('type', ['country', 'state', 'city', 'suburb'])
                    ->name('edit');
                Route::patch('{type}/{location}', [LocationsManagementController::class, 'update'])
                    ->whereIn('type', ['country', 'state', 'city', 'suburb'])
                    ->name('update');
                Route::delete('{type}/{location}', [LocationsManagementController::class, 'destroy'])
                    ->whereIn('type', ['country', 'state', 'city', 'suburb'])
                    ->name('destroy');
            });

        Route::prefix('dealer-management')
            ->as('dealer-management.')
            ->group(function () {
                Route::get('dealers', [DealersController::class, 'index'])
                    ->name('dealers.index');
                Route::get('dealers/create', [DealersController::class, 'create'])
                    ->name('dealers.create');
                Route::post('dealers', [DealersController::class, 'store'])
                    ->name('dealers.store');
                Route::get('dealers/{dealer}', [OverviewsController::class, 'show'])
                    ->name('dealers.overview');
                Route::get('dealers/{dealer}/branches', [BranchesController::class, 'show'])
                    ->name('dealers.branches');
                Route::get('dealers/{dealer}/branches/create', [BranchesController::class, 'create'])
                    ->name('dealers.branches.create');
                Route::post('dealers/{dealer}/branches', [BranchesController::class, 'store'])
                    ->name('dealers.branches.store');
                Route::get('dealers/{dealer}/branches/{branch}/edit', [BranchesController::class, 'edit'])
                    ->name('dealers.branches.edit');
                Route::patch('dealers/{dealer}/branches/{branch}', [BranchesController::class, 'update'])
                    ->name('dealers.branches.update');
                Route::delete('dealers/{dealer}/branches/{branch}', [BranchesController::class, 'destroy'])
                    ->name('dealers.branches.destroy');
                Route::get('dealers/{dealer}/sales-people', [SalesPeopleController::class, 'show'])
                    ->name('dealers.sales-people');
                Route::get('dealers/{dealer}/sales-people/{salesPerson}/edit', [SalesPeopleController::class, 'edit'])
                    ->name('dealers.sales-people.edit');
                Route::patch('dealers/{dealer}/sales-people/{salesPerson}', [SalesPeopleController::class, 'update'])
                    ->name('dealers.sales-people.update');
                Route::delete('dealers/{dealer}/sales-people/{salesPerson}', [SalesPeopleController::class, 'destroy'])
                    ->name('dealers.sales-people.destroy');
                Route::get('dealers/{dealer}/users', [UsersController::class, 'show'])
                    ->name('dealers.users');
                Route::get('dealers/{dealer}/users/{dealerUser}/edit', [UsersController::class, 'edit'])
                    ->name('dealers.users.edit');
                Route::patch('dealers/{dealer}/users/{dealerUser}', [UsersController::class, 'update'])
                    ->name('dealers.users.update');
                Route::delete('dealers/{dealer}/users/{dealerUser}', [UsersController::class, 'destroy'])
                    ->name('dealers.users.destroy');
                Route::post('dealers/{dealer}/users/{dealerUser}/reset-password', [UsersController::class, 'resetPassword'])
                    ->name('dealers.users.reset-password');
                Route::get('dealers/{dealer}/stock', [StockController::class, 'show'])
                    ->name('dealers.stock');
                Route::get('dealers/{dealer}/notes', [NotesController::class, 'show'])
                    ->name('dealers.notes');
                Route::get('dealers/{dealer}/notification-history', [NotificationHistoriesController::class, 'show'])
                    ->name('dealers.notification-history');
                Route::get('dealers/{dealer}/settings', [SettingsController::class, 'show'])
                    ->name('dealers.settings');
                Route::get('dealers/{dealer}/billings', [BillingsController::class, 'show'])
                    ->name('dealers.billings');
                Route::get('dealers/{dealer}/audit-log', [AuditLogsController::class, 'show'])
                    ->name('dealers.audit-log');
                Route::get('dealers/{dealer}/edit', [DealersController::class, 'edit'])
                    ->name('dealers.edit');
                Route::patch('dealers/{dealer}', [DealersController::class, 'update'])
                    ->name('dealers.update');
                Route::patch('dealers/{dealer}/activate', [DealersController::class, 'activate'])
                    ->name('dealers.activate');
                Route::patch('dealers/{dealer}/deactivate', [DealersController::class, 'deactivate'])
                    ->name('dealers.deactivate');
                Route::delete('dealers/{dealer}', [DealersController::class, 'destroy'])
                    ->name('dealers.destroy');
            });
    });

    Route::group(['middleware' => 'auth:dealer'], function () {
        Route::prefix('configuration')
            ->as('dealer-configuration.')
            ->group(function () {
                Route::get('edit-dealership', [DealerConfigurationEditDealershipController::class, 'show'])
                    ->name('edit-dealership.show');

                Route::get('branches', [DealerConfigurationBranchesController::class, 'index'])
                    ->name('branches.index');
                Route::get('branches/create', [DealerConfigurationBranchesController::class, 'create'])
                    ->name('branches.create');
                Route::post('branches', [DealerConfigurationBranchesController::class, 'store'])
                    ->name('branches.store');
                Route::get('branches/{branch}/edit', [DealerConfigurationBranchesController::class, 'edit'])
                    ->name('branches.edit');
                Route::patch('branches/{branch}', [DealerConfigurationBranchesController::class, 'update'])
                    ->name('branches.update');
                Route::delete('branches/{branch}', [DealerConfigurationBranchesController::class, 'destroy'])
                    ->name('branches.destroy');

                Route::get('sales-people', [DealerConfigurationSalesPeopleController::class, 'index'])
                    ->name('sales-people.index');
                Route::get('sales-people/{salesPerson}/edit', [DealerConfigurationSalesPeopleController::class, 'edit'])
                    ->name('sales-people.edit');
                Route::patch('sales-people/{salesPerson}', [DealerConfigurationSalesPeopleController::class, 'update'])
                    ->name('sales-people.update');
                Route::delete('sales-people/{salesPerson}', [DealerConfigurationSalesPeopleController::class, 'destroy'])
                    ->name('sales-people.destroy');

                Route::get('users', [DealerConfigurationUsersController::class, 'index'])
                    ->name('users.index');
                Route::get('users/{dealerUser}/edit', [DealerConfigurationUsersController::class, 'edit'])
                    ->name('users.edit');
                Route::patch('users/{dealerUser}', [DealerConfigurationUsersController::class, 'update'])
                    ->name('users.update');
                Route::delete('users/{dealerUser}', [DealerConfigurationUsersController::class, 'destroy'])
                    ->name('users.destroy');
                Route::post('users/{dealerUser}/reset-password', [DealerConfigurationUsersController::class, 'resetPassword'])
                    ->name('users.reset-password');
            });
    });
});
