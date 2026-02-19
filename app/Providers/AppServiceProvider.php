<?php

namespace App\Providers;

use App\Models\System\User;
use App\Models\System\SystemRequest;
use App\Models\System\Configuration\SystemConfiguration;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerSalePerson;
use App\Models\Location\LocationCity;
use App\Models\Location\LocationCountry;
use App\Models\Location\LocationState;
use App\Models\Location\LocationSuburb;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadPipeline;
use App\Models\Leads\LeadStage;
use App\Models\Invoice\Invoice;
use App\Models\Quotation\Quotation;
use App\Models\Stock\Stock;
use App\Policies\Backoffice\Auth\ImpersonationsPolicy;
use App\Policies\Backoffice\DealerManagement\DealersManagementPolicy;
use App\Policies\Backoffice\System\LocationsManagementPolicy;
use App\Policies\Backoffice\System\SystemConfigurationsPolicy;
use App\Policies\Backoffice\System\SystemInvoicesPolicy;
use App\Policies\Backoffice\System\SystemQuotationsPolicy;
use App\Policies\Backoffice\System\UsersManagementPolicy;
use App\Observers\SystemRequestObserver;
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
        SystemRequest::observe(SystemRequestObserver::class);
        Gate::policy(Dealer::class, DealersManagementPolicy::class);
        Gate::policy(DealerUser::class, ImpersonationsPolicy::class);
        Gate::policy(LocationCountry::class, LocationsManagementPolicy::class);
        Gate::policy(LocationState::class, LocationsManagementPolicy::class);
        Gate::policy(LocationCity::class, LocationsManagementPolicy::class);
        Gate::policy(LocationSuburb::class, LocationsManagementPolicy::class);
        Gate::policy(SystemConfiguration::class, SystemConfigurationsPolicy::class);
        Gate::policy(Quotation::class, SystemQuotationsPolicy::class);
        Gate::policy(Invoice::class, SystemInvoicesPolicy::class);

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

        Gate::define('dealerConfigurationCreateSalesPerson', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('createDealershipSalesPeople', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to create sales people.');
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

        Gate::define('dealerConfigurationCreateUser', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('createDealershipUsers', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to create users.');
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

        Gate::define('dealerConfigurationAssignUserPermissions', function ($actor, DealerUser $dealerUser): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealerUser->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('assignPermissions', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to assign user permissions.');
        });

        Gate::define('dealerConfigurationShowNotes', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('showNotes', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to view notes.');
        });

        Gate::define('dealerConfigurationIndexStock', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('indexStock', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to view stock.');
        });

        Gate::define('dealerConfigurationShowStock', function ($actor, Stock $stock): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            $stock->loadMissing('branch:id,dealer_id');
            if ((string) $actor->dealer_id !== (string) $stock->branch?->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('showStock', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to view stock.');
        });

        Gate::define('dealerConfigurationCreateStock', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('createStock', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to create stock.');
        });

        Gate::define('dealerConfigurationEditStock', function ($actor, Stock $stock): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            $stock->loadMissing('branch:id,dealer_id');
            if ((string) $actor->dealer_id !== (string) $stock->branch?->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('editStock', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to edit stock.');
        });

        Gate::define('dealerConfigurationDeleteStock', function ($actor, Stock $stock): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            $stock->loadMissing('branch:id,dealer_id');
            if ((string) $actor->dealer_id !== (string) $stock->branch?->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('deleteStock', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to delete stock.');
        });

        Gate::define('dealerConfigurationConfigureSettings', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('canConfigureSettings', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to configure settings.');
        });

        Gate::define('dealerConfigurationManageLeads', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('manageLeads', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to manage leads.');
        });

        Gate::define('dealerConfigurationCreateLead', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('manageLeads', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to create leads.');
        });

        Gate::define('dealerConfigurationViewLead', function ($actor, Lead $lead): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $lead->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('manageLeads', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to view leads.');
        });

        Gate::define('dealerConfigurationEditLead', function ($actor, Lead $lead): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $lead->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('manageLeads', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to edit leads.');
        });

        Gate::define('dealerConfigurationDeleteLead', function ($actor, Lead $lead): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $lead->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('manageLeads', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to delete leads.');
        });

        Gate::define('dealerConfigurationIndexPipelines', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('indexPipelines', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to view pipelines.');
        });

        Gate::define('dealerConfigurationCreatePipeline', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('createPipelines', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to create pipelines.');
        });

        Gate::define('dealerConfigurationEditPipeline', function ($actor, LeadPipeline $pipeline): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $pipeline->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('editPipelines', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to edit pipelines.');
        });

        Gate::define('dealerConfigurationDeletePipeline', function ($actor, LeadPipeline $pipeline): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $pipeline->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('deletePipelines', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to delete pipelines.');
        });

        Gate::define('dealerConfigurationIndexPipelineStages', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('indexPipelineStages', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to view pipeline stages.');
        });

        Gate::define('dealerConfigurationCreatePipelineStage', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('createPipelineStages', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to create pipeline stages.');
        });

        Gate::define('dealerConfigurationEditPipelineStage', function ($actor, LeadStage $stage): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            $stage->loadMissing('pipeline:id,dealer_id');
            if ((string) $actor->dealer_id !== (string) $stage->pipeline?->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('editPipelineStages', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to edit pipeline stages.');
        });

        Gate::define('dealerConfigurationDeletePipelineStage', function ($actor, LeadStage $stage): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            $stage->loadMissing('pipeline:id,dealer_id');
            if ((string) $actor->dealer_id !== (string) $stage->pipeline?->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('deletePipelineStages', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to delete pipeline stages.');
        });

        Gate::define('dealerConfigurationIndexQuotations', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('indexQuotations', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to view quotations.');
        });

        Gate::define('dealerConfigurationCreateQuotation', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('createDealershipQuotations', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to create quotations.');
        });

        Gate::define('dealerConfigurationEditQuotation', function ($actor, Quotation $quotation): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $quotation->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('editDealershipQuotations', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to edit quotations.');
        });

        Gate::define('dealerConfigurationDeleteQuotation', function ($actor, Quotation $quotation): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $quotation->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('deleteDealershipQuotations', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to delete quotations.');
        });

        Gate::define('dealerConfigurationIndexInvoices', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('indexInvoices', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to view invoices.');
        });

        Gate::define('dealerConfigurationCreateInvoice', function ($actor, Dealer $dealer): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $dealer->id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('createDealershipInvoices', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to create invoices.');
        });

        Gate::define('dealerConfigurationEditInvoice', function ($actor, Invoice $invoice): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $invoice->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('editDealershipInvoices', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to edit invoices.');
        });

        Gate::define('dealerConfigurationDeleteInvoice', function ($actor, Invoice $invoice): Response {
            if (! $actor instanceof DealerUser) {
                return Response::deny('Invalid actor.');
            }

            if ((string) $actor->dealer_id !== (string) $invoice->dealer_id) {
                return Response::deny('Dealer mismatch.');
            }

            return $actor->hasPermissionTo('deleteDealershipInvoices', 'dealer')
                ? Response::allow()
                : Response::deny('You do not have permission to delete invoices.');
        });
    }
}
