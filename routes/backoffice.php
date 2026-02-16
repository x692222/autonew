<?php

use App\Http\Controllers\Backoffice\GuardBackoffice\Auth\ForgotPasswordController;
use App\Http\Controllers\Backoffice\GuardBackoffice\Auth\DealerResetPasswordController;
use App\Http\Controllers\Backoffice\GuardBackoffice\Auth\ImpersonationsController;
use App\Http\Controllers\Backoffice\GuardBackoffice\Auth\LoginController;
use App\Http\Controllers\Backoffice\GuardBackoffice\Auth\ResetPasswordController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\DealersController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\AuditLogsController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\BillingsController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\BranchesController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\NotificationHistoriesController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\OverviewsController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\QuotationsController as DealerManagementQuotationsController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\LeadsController as DealerManagementLeadsController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\LeadPipelinesController as DealerManagementLeadPipelinesController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\LeadStagesController as DealerManagementLeadStagesController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\SalesPeopleController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\SettingsController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\StockImagesController as DealerManagementStockImagesController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\StocksController as DealerManagementStocksController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\UsersController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers\UserPermissionsController as DealerManagementUserPermissionsController;
use App\Http\Controllers\Backoffice\GuardBackoffice\System\AccessManagementController;
use App\Http\Controllers\Backoffice\GuardBackoffice\DashboardController;
use App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration\BranchesController as DealerConfigurationBranchesController;
use App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration\EditDealershipController as DealerConfigurationEditDealershipController;
use App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration\SalesPeopleController as DealerConfigurationSalesPeopleController;
use App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration\SettingsController as DealerConfigurationSettingsController;
use App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration\LeadsController as DealerConfigurationLeadsController;
use App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration\LeadPipelinesController as DealerConfigurationLeadPipelinesController;
use App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration\LeadStagesController as DealerConfigurationLeadStagesController;
use App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration\StockImagesController as DealerConfigurationStockImagesController;
use App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration\StocksController as DealerConfigurationStocksController;
use App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration\QuotationsController as DealerConfigurationQuotationsController;
use App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration\UsersController as DealerConfigurationUsersController;
use App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration\UserPermissionsController as DealerConfigurationUserPermissionsController;
use App\Http\Controllers\Backoffice\Shared\NotesController as BackofficeNotesController;
use App\Http\Controllers\Backoffice\Shared\QuotationLookupsController;
use App\Http\Controllers\Backoffice\GuardBackoffice\System\LocationsManagementController;
use App\Http\Controllers\Backoffice\GuardBackoffice\System\PendingFeatureTagsController;
use App\Http\Controllers\Backoffice\GuardBackoffice\System\SystemConfigurationsController;
use App\Http\Controllers\Backoffice\GuardBackoffice\System\QuotationsController as SystemQuotationsController;
use App\Http\Controllers\Backoffice\GuardBackoffice\System\SystemRequestsController;
use App\Http\Controllers\Backoffice\GuardBackoffice\System\UsersManagementController;
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

    Route::prefix('notes')
        ->as('notes.')
        ->middleware(['auth:backoffice,dealer', 'ajax'])
        ->group(function () {
            Route::get('{noteableType}/{noteableId}', [BackofficeNotesController::class, 'index'])->name('index');
            Route::post('{noteableType}/{noteableId}', [BackofficeNotesController::class, 'store'])->name('store');
            Route::patch('{noteableType}/{noteableId}/{note}', [BackofficeNotesController::class, 'update'])->name('update');
            Route::delete('{noteableType}/{noteableId}/{note}', [BackofficeNotesController::class, 'destroy'])->name('destroy');
        });

    Route::prefix('system/requests')
        ->as('system.requests.')
        ->middleware(['auth:backoffice,dealer', 'ajax'])
        ->group(function () {
            Route::post('/', [SystemRequestsController::class, 'store'])->name('store');
        });

    Route::group(['middleware' => ['auth:backoffice', 'block-backoffice-while-impersonating']], function () {
        Route::get('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/test', [DashboardController::class, 'test'])->name('test');
        Route::post('/auth/impersonations/start', [ImpersonationsController::class, 'start'])->name('auth.impersonations.start');
        Route::post('/auth/impersonations/stop', [ImpersonationsController::class, 'stop'])
            ->withoutMiddleware('block-backoffice-while-impersonating')
            ->name('auth.impersonations.stop');

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

        Route::prefix('system')
            ->as('system.')
            ->group(function () {
                Route::resource('system-requests', SystemRequestsController::class)
                    ->parameters(['system-requests' => 'systemRequest'])
                    ->only(['index', 'update', 'destroy']);
                Route::resource('quotations', SystemQuotationsController::class)
                    ->parameters(['quotations' => 'quotation'])
                    ->except(['show']);
                Route::get('quotations/{quotation}/export', [SystemQuotationsController::class, 'export'])
                    ->name('quotations.export');
                Route::get('settings', [SystemConfigurationsController::class, 'index'])
                    ->name('settings.index');
                Route::patch('settings', [SystemConfigurationsController::class, 'update'])
                    ->name('settings.update');

                Route::get('pending-feature-tags', [PendingFeatureTagsController::class, 'index'])
                    ->name('pending-feature-tags.index');
                Route::patch('pending-feature-tags/{stockFeatureTag}', [PendingFeatureTagsController::class, 'update'])
                    ->name('pending-feature-tags.update');
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
                Route::get('dealers/{dealer}/sales-people/create', [SalesPeopleController::class, 'create'])
                    ->name('dealers.sales-people.create');
                Route::post('dealers/{dealer}/sales-people', [SalesPeopleController::class, 'store'])
                    ->name('dealers.sales-people.store');
                Route::get('dealers/{dealer}/sales-people/{salesPerson}/edit', [SalesPeopleController::class, 'edit'])
                    ->name('dealers.sales-people.edit');
                Route::patch('dealers/{dealer}/sales-people/{salesPerson}', [SalesPeopleController::class, 'update'])
                    ->name('dealers.sales-people.update');
                Route::delete('dealers/{dealer}/sales-people/{salesPerson}', [SalesPeopleController::class, 'destroy'])
                    ->name('dealers.sales-people.destroy');
                Route::get('dealers/{dealer}/users', [UsersController::class, 'show'])
                    ->name('dealers.users');
                Route::get('dealers/{dealer}/users/create', [UsersController::class, 'create'])
                    ->name('dealers.users.create');
                Route::post('dealers/{dealer}/users', [UsersController::class, 'store'])
                    ->name('dealers.users.store');
                Route::get('dealers/{dealer}/users/{dealerUser}/edit', [UsersController::class, 'edit'])
                    ->name('dealers.users.edit');
                Route::patch('dealers/{dealer}/users/{dealerUser}', [UsersController::class, 'update'])
                    ->name('dealers.users.update');
                Route::delete('dealers/{dealer}/users/{dealerUser}', [UsersController::class, 'destroy'])
                    ->name('dealers.users.destroy');
                Route::post('dealers/{dealer}/users/{dealerUser}/reset-password', [UsersController::class, 'resetPassword'])
                    ->name('dealers.users.reset-password');
                Route::get('dealers/{dealer}/users/{dealerUser}/permissions', [DealerManagementUserPermissionsController::class, 'edit'])
                    ->name('dealers.users.permissions.edit');
                Route::patch('dealers/{dealer}/users/{dealerUser}/permissions', [DealerManagementUserPermissionsController::class, 'update'])
                    ->name('dealers.users.permissions.update');
                Route::get('dealers/{dealer}/stock', [DealerManagementStocksController::class, 'index'])
                    ->name('dealers.stock');
                Route::get('dealers/{dealer}/stock/create', [DealerManagementStocksController::class, 'create'])
                    ->name('dealers.stock.create');
                Route::post('dealers/{dealer}/stock', [DealerManagementStocksController::class, 'store'])
                    ->name('dealers.stock.store');
                Route::get('dealers/{dealer}/stock/{stock}', [DealerManagementStocksController::class, 'show'])
                    ->name('dealers.stock.show');
                Route::get('dealers/{dealer}/stock/{stock}/edit', [DealerManagementStocksController::class, 'edit'])
                    ->name('dealers.stock.edit');
                Route::patch('dealers/{dealer}/stock/{stock}', [DealerManagementStocksController::class, 'update'])
                    ->name('dealers.stock.update');
                Route::delete('dealers/{dealer}/stock/{stock}', [DealerManagementStocksController::class, 'destroy'])
                    ->name('dealers.stock.destroy');
                Route::patch('dealers/{dealer}/stock/{stock}/mark-sold', [DealerManagementStocksController::class, 'markSold'])
                    ->name('dealers.stock.mark-sold');
                Route::patch('dealers/{dealer}/stock/{stock}/mark-unsold', [DealerManagementStocksController::class, 'markUnsold'])
                    ->name('dealers.stock.mark-unsold');
                Route::patch('dealers/{dealer}/stock/{stock}/activate', [DealerManagementStocksController::class, 'activate'])
                    ->name('dealers.stock.activate');
                Route::patch('dealers/{dealer}/stock/{stock}/deactivate', [DealerManagementStocksController::class, 'deactivate'])
                    ->name('dealers.stock.deactivate');
                Route::get('dealers/{dealer}/leads', [DealerManagementLeadsController::class, 'show'])
                    ->name('dealers.leads');
                Route::get('dealers/{dealer}/leads/create', [DealerManagementLeadsController::class, 'create'])
                    ->name('dealers.leads.create');
                Route::post('dealers/{dealer}/leads', [DealerManagementLeadsController::class, 'store'])
                    ->name('dealers.leads.store');
                Route::get('dealers/{dealer}/leads/{lead}', [DealerManagementLeadsController::class, 'overview'])
                    ->name('dealers.leads.overview');
                Route::get('dealers/{dealer}/leads/{lead}/conversations', [DealerManagementLeadsController::class, 'conversations'])
                    ->name('dealers.leads.conversations');
                Route::get('dealers/{dealer}/leads/{lead}/stage-history', [DealerManagementLeadsController::class, 'stageHistory'])
                    ->name('dealers.leads.stage-history');
                Route::get('dealers/{dealer}/leads/{lead}/edit', [DealerManagementLeadsController::class, 'edit'])
                    ->name('dealers.leads.edit');
                Route::patch('dealers/{dealer}/leads/{lead}', [DealerManagementLeadsController::class, 'update'])
                    ->name('dealers.leads.update');
                Route::delete('dealers/{dealer}/leads/{lead}', [DealerManagementLeadsController::class, 'destroy'])
                    ->name('dealers.leads.destroy');
                Route::prefix('dealers/{dealer}/lead-pipelines')
                    ->as('dealers.lead-pipelines.')
                    ->group(function () {
                        Route::get('/', [DealerManagementLeadPipelinesController::class, 'index'])->name('index');
                        Route::get('/create', [DealerManagementLeadPipelinesController::class, 'create'])->name('create');
                        Route::post('/', [DealerManagementLeadPipelinesController::class, 'store'])->name('store');
                        Route::get('/{leadPipeline}/edit', [DealerManagementLeadPipelinesController::class, 'edit'])->name('edit');
                        Route::patch('/{leadPipeline}', [DealerManagementLeadPipelinesController::class, 'update'])->name('update');
                        Route::delete('/{leadPipeline}', [DealerManagementLeadPipelinesController::class, 'destroy'])->name('destroy');
                    });
                Route::prefix('dealers/{dealer}/lead-stages')
                    ->as('dealers.lead-stages.')
                    ->group(function () {
                        Route::get('/', [DealerManagementLeadStagesController::class, 'index'])->name('index');
                        Route::get('/create', [DealerManagementLeadStagesController::class, 'create'])->name('create');
                        Route::post('/', [DealerManagementLeadStagesController::class, 'store'])->name('store');
                        Route::get('/{leadStage}/edit', [DealerManagementLeadStagesController::class, 'edit'])->name('edit');
                        Route::patch('/{leadStage}', [DealerManagementLeadStagesController::class, 'update'])->name('update');
                        Route::delete('/{leadStage}', [DealerManagementLeadStagesController::class, 'destroy'])->name('destroy');
                    });
                Route::resource('dealers/{dealer}/quotations', DealerManagementQuotationsController::class)
                    ->parameters(['quotations' => 'quotation'])
                    ->names('dealers.quotations')
                    ->except(['show']);
                Route::get('dealers/{dealer}/quotations/{quotation}/export', [DealerManagementQuotationsController::class, 'export'])
                    ->name('dealers.quotations.export');
                Route::get('dealers/{dealer}/notification-history', [NotificationHistoriesController::class, 'show'])
                    ->name('dealers.notification-history');
                Route::get('dealers/{dealer}/settings', [SettingsController::class, 'show'])
                    ->name('dealers.settings');
                Route::patch('dealers/{dealer}/settings', [SettingsController::class, 'update'])
                    ->name('dealers.settings.update');
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

        Route::prefix('dealer-management')
            ->as('dealer-management.')
            ->middleware('ajax')
            ->group(function () {
                Route::get('dealers/{dealer}/stock/{stock}/images', [DealerManagementStockImagesController::class, 'index'])
                    ->name('dealers.stock.images.index');
                Route::post('dealers/{dealer}/stock/{stock}/images/upload', [DealerManagementStockImagesController::class, 'upload'])
                    ->name('dealers.stock.images.upload');
                Route::post('dealers/{dealer}/stock/{stock}/images/assign', [DealerManagementStockImagesController::class, 'assign'])
                    ->name('dealers.stock.images.assign');
                Route::delete('dealers/{dealer}/stock/{stock}/images/{media}', [DealerManagementStockImagesController::class, 'destroy'])
                    ->name('dealers.stock.images.destroy');
                Route::patch('dealers/{dealer}/stock/{stock}/images/reorder', [DealerManagementStockImagesController::class, 'reorder'])
                    ->name('dealers.stock.images.reorder');
                Route::post('dealers/{dealer}/stock/{stock}/images/move-back-to-bucket', [DealerManagementStockImagesController::class, 'moveBackToBucket'])
                    ->name('dealers.stock.images.move-back-to-bucket');
                Route::get('dealers/{dealer}/quotations/customers/search', [QuotationLookupsController::class, 'searchDealerCustomers'])
                    ->name('dealers.quotations.customers.search');
                Route::post('dealers/{dealer}/quotations/customers', [QuotationLookupsController::class, 'storeDealerCustomer'])
                    ->name('dealers.quotations.customers.store');
                Route::get('dealers/{dealer}/quotations/line-item-suggestions', [QuotationLookupsController::class, 'lineItemSuggestionsForDealer'])
                    ->name('dealers.quotations.line-item-suggestions');
            });

        Route::prefix('system/quotations')
            ->as('system.quotations.')
            ->middleware('ajax')
            ->group(function () {
                Route::get('customers/search', [QuotationLookupsController::class, 'searchSystemCustomers'])
                    ->name('customers.search');
                Route::post('customers', [QuotationLookupsController::class, 'storeSystemCustomer'])
                    ->name('customers.store');
                Route::get('line-item-suggestions', [QuotationLookupsController::class, 'lineItemSuggestionsForSystem'])
                    ->name('line-item-suggestions');
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
                Route::get('sales-people/create', [DealerConfigurationSalesPeopleController::class, 'create'])
                    ->name('sales-people.create');
                Route::post('sales-people', [DealerConfigurationSalesPeopleController::class, 'store'])
                    ->name('sales-people.store');
                Route::get('sales-people/{salesPerson}/edit', [DealerConfigurationSalesPeopleController::class, 'edit'])
                    ->name('sales-people.edit');
                Route::patch('sales-people/{salesPerson}', [DealerConfigurationSalesPeopleController::class, 'update'])
                    ->name('sales-people.update');
                Route::delete('sales-people/{salesPerson}', [DealerConfigurationSalesPeopleController::class, 'destroy'])
                    ->name('sales-people.destroy');

                Route::get('users', [DealerConfigurationUsersController::class, 'index'])
                    ->name('users.index');
                Route::get('users/create', [DealerConfigurationUsersController::class, 'create'])
                    ->name('users.create');
                Route::post('users', [DealerConfigurationUsersController::class, 'store'])
                    ->name('users.store');
                Route::get('users/{dealerUser}/edit', [DealerConfigurationUsersController::class, 'edit'])
                    ->name('users.edit');
                Route::patch('users/{dealerUser}', [DealerConfigurationUsersController::class, 'update'])
                    ->name('users.update');
                Route::delete('users/{dealerUser}', [DealerConfigurationUsersController::class, 'destroy'])
                    ->name('users.destroy');
                Route::post('users/{dealerUser}/reset-password', [DealerConfigurationUsersController::class, 'resetPassword'])
                    ->name('users.reset-password');
                Route::get('users/{dealerUser}/permissions', [DealerConfigurationUserPermissionsController::class, 'edit'])
                    ->name('users.permissions.edit');
                Route::patch('users/{dealerUser}/permissions', [DealerConfigurationUserPermissionsController::class, 'update'])
                    ->name('users.permissions.update');
                Route::get('stock', [DealerConfigurationStocksController::class, 'index'])
                    ->name('stock.index');
                Route::get('stock/create', [DealerConfigurationStocksController::class, 'create'])
                    ->name('stock.create');
                Route::post('stock', [DealerConfigurationStocksController::class, 'store'])
                    ->name('stock.store');
                Route::get('stock/{stock}', [DealerConfigurationStocksController::class, 'show'])
                    ->name('stock.show');
                Route::get('stock/{stock}/edit', [DealerConfigurationStocksController::class, 'edit'])
                    ->name('stock.edit');
                Route::patch('stock/{stock}', [DealerConfigurationStocksController::class, 'update'])
                    ->name('stock.update');
                Route::delete('stock/{stock}', [DealerConfigurationStocksController::class, 'destroy'])
                    ->name('stock.destroy');
                Route::patch('stock/{stock}/mark-sold', [DealerConfigurationStocksController::class, 'markSold'])
                    ->name('stock.mark-sold');
                Route::patch('stock/{stock}/mark-unsold', [DealerConfigurationStocksController::class, 'markUnsold'])
                    ->name('stock.mark-unsold');
                Route::get('settings', [DealerConfigurationSettingsController::class, 'index'])
                    ->name('settings.index');
                Route::patch('settings', [DealerConfigurationSettingsController::class, 'update'])
                    ->name('settings.update');
                Route::get('leads', [DealerConfigurationLeadsController::class, 'index'])
                    ->name('leads.index');
                Route::get('leads/create', [DealerConfigurationLeadsController::class, 'create'])
                    ->name('leads.create');
                Route::post('leads', [DealerConfigurationLeadsController::class, 'store'])
                    ->name('leads.store');
                Route::get('leads/{lead}', [DealerConfigurationLeadsController::class, 'overview'])
                    ->name('leads.overview');
                Route::get('leads/{lead}/conversations', [DealerConfigurationLeadsController::class, 'conversations'])
                    ->name('leads.conversations');
                Route::get('leads/{lead}/stage-history', [DealerConfigurationLeadsController::class, 'stageHistory'])
                    ->name('leads.stage-history');
                Route::get('leads/{lead}/edit', [DealerConfigurationLeadsController::class, 'edit'])
                    ->name('leads.edit');
                Route::patch('leads/{lead}', [DealerConfigurationLeadsController::class, 'update'])
                    ->name('leads.update');
                Route::delete('leads/{lead}', [DealerConfigurationLeadsController::class, 'destroy'])
                    ->name('leads.destroy');
                Route::resource('lead-pipelines', DealerConfigurationLeadPipelinesController::class)
                    ->parameters(['lead-pipelines' => 'leadPipeline'])
                    ->except(['show']);
                Route::resource('lead-stages', DealerConfigurationLeadStagesController::class)
                    ->parameters(['lead-stages' => 'leadStage'])
                    ->except(['show']);
                Route::resource('quotations', DealerConfigurationQuotationsController::class)
                    ->parameters(['quotations' => 'quotation'])
                    ->except(['show']);
                Route::get('quotations/{quotation}/export', [DealerConfigurationQuotationsController::class, 'export'])
                    ->name('quotations.export');
            });

        Route::prefix('configuration')
            ->as('dealer-configuration.')
            ->middleware('ajax')
            ->group(function () {
                Route::get('stock/{stock}/images', [DealerConfigurationStockImagesController::class, 'index'])
                    ->name('stock.images.index');
                Route::post('stock/{stock}/images/upload', [DealerConfigurationStockImagesController::class, 'upload'])
                    ->name('stock.images.upload');
                Route::post('stock/{stock}/images/assign', [DealerConfigurationStockImagesController::class, 'assign'])
                    ->name('stock.images.assign');
                Route::delete('stock/{stock}/images/{media}', [DealerConfigurationStockImagesController::class, 'destroy'])
                    ->name('stock.images.destroy');
                Route::patch('stock/{stock}/images/reorder', [DealerConfigurationStockImagesController::class, 'reorder'])
                    ->name('stock.images.reorder');
                Route::post('stock/{stock}/images/move-back-to-bucket', [DealerConfigurationStockImagesController::class, 'moveBackToBucket'])
                    ->name('stock.images.move-back-to-bucket');
                Route::get('quotations/customers/search', [QuotationLookupsController::class, 'searchDealerConfigurationCustomers'])
                    ->name('quotations.customers.search');
                Route::post('quotations/customers', [QuotationLookupsController::class, 'storeDealerConfigurationCustomer'])
                    ->name('quotations.customers.store');
                Route::get('quotations/line-item-suggestions', [QuotationLookupsController::class, 'lineItemSuggestionsForDealerConfiguration'])
                    ->name('quotations.line-item-suggestions');
            });
    });
});
