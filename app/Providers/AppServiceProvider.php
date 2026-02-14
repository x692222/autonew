<?php

namespace App\Providers;

use App\Models\System\User;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerSalePerson;
use App\Models\Location\LocationCity;
use App\Models\Location\LocationCountry;
use App\Models\Location\LocationState;
use App\Models\Location\LocationSuburb;
use App\Policies\Backoffice\Auth\ImpersonationsPolicy;
use App\Policies\Backoffice\DealerManagement\DealersManagementPolicy;
use App\Policies\Backoffice\System\LocationsManagementPolicy;
use App\Policies\Backoffice\System\UsersManagementPolicy;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UsersManagementPolicy::class);
        Gate::policy(Dealer::class, DealersManagementPolicy::class);
        Gate::policy(DealerUser::class, ImpersonationsPolicy::class);
        Gate::policy(LocationCountry::class, LocationsManagementPolicy::class);
        Gate::policy(LocationState::class, LocationsManagementPolicy::class);
        Gate::policy(LocationCity::class, LocationsManagementPolicy::class);
        Gate::policy(LocationSuburb::class, LocationsManagementPolicy::class);

        Gate::define('dealerConfigurationEditDealership', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('editDealership', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to edit dealership.');
        });

        Gate::define('dealerConfigurationIndexBranches', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('indexDealershipBranches', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to view branches.');
        });

        Gate::define('dealerConfigurationEditBranch', function ($actor, DealerBranch $branch): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $branch->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('editDealershipBranches', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to edit branches.');
        });

        Gate::define('dealerConfigurationDeleteBranch', function ($actor, DealerBranch $branch): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $branch->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('deleteDealershipBranches', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to delete branches.');
        });

        Gate::define('dealerConfigurationIndexSalesPeople', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('indexDealershipSalesPeople', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to view sales people.');
        });

        Gate::define('dealerConfigurationEditSalesPerson', function ($actor, DealerSalePerson $salesPerson): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            $salesPerson->loadMissing('branch:id,dealer_id');
            if ((string) $actor->dealer_id !== (string) $salesPerson->branch?->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('editDealershipSalesPeople', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to edit sales people.');
        });

        Gate::define('dealerConfigurationDeleteSalesPerson', function ($actor, DealerSalePerson $salesPerson): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            $salesPerson->loadMissing('branch:id,dealer_id');
            if ((string) $actor->dealer_id !== (string) $salesPerson->branch?->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('deleteDealershipSalesPeople', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to delete sales people.');
        });

        Gate::define('dealerConfigurationIndexUsers', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('indexDealershipUsers', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to view users.');
        });

        Gate::define('dealerConfigurationEditUser', function ($actor, DealerUser $dealerUser): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealerUser->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('editDealershipUsers', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to edit users.');
        });

        Gate::define('dealerConfigurationDeleteUser', function ($actor, DealerUser $dealerUser): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealerUser->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            if ((string) $actor->id === (string) $dealerUser->id) {
                return Response::deny('You cannot delete your own account.');
            }

            return $actor->hasPermissionTo('deleteDealershipUsers', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to delete users.');
        });

        Gate::define('dealerConfigurationResetUserPassword', function ($actor, DealerUser $dealerUser): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealerUser->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('resetDealershipUserPasswords', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to reset user passwords.');
        });
    }
}
