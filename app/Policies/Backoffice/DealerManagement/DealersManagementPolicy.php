<?php

namespace App\Policies\Backoffice\DealerManagement;

use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerSalePerson;
use App\Models\Dealer\DealerUser;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadPipeline;
use App\Models\Leads\LeadStage;
use App\Models\Quotation\Quotation;
use App\Models\Stock\Stock;
use App\Models\System\User;
use Illuminate\Auth\Access\Response;

class DealersManagementPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('indexDealerships', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view dealerships.');
    }

    public function view(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('showDealerships', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view dealerships.');
    }

    public function show(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('showDealerships', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view dealerships.');
    }

    public function showBranches(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('indexDealershipBranches', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view dealer branches.');
    }

    public function showSalesPeople(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('indexDealershipSalesPeople', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view dealer sales people.');
    }

    public function showUsers(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('indexDealershipUsers', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view dealer users.');
    }

    public function showStock(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('indexDealershipStock', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view dealer stock.');
    }

    public function showLeads(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('manageDealershipLeads', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view dealer leads.');
    }

    public function viewLead(User $user, Dealer $dealer, Lead $lead): Response
    {
        if ((string) $lead->dealer_id !== (string) $dealer->id) {
            return Response::deny('This lead does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('manageDealershipLeads', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view this lead.');
    }

    public function createLead(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('manageDealershipLeads', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create leads.');
    }

    public function editLead(User $user, Dealer $dealer, Lead $lead): Response
    {
        if ((string) $lead->dealer_id !== (string) $dealer->id) {
            return Response::deny('This lead does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('manageDealershipLeads', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit this lead.');
    }

    public function deleteLead(User $user, Dealer $dealer, Lead $lead): Response
    {
        if ((string) $lead->dealer_id !== (string) $dealer->id) {
            return Response::deny('This lead does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('manageDealershipLeads', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete this lead.');
    }

    public function showLeadPipelines(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('indexDealershipPipelines', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view lead pipelines.');
    }

    public function createLeadPipeline(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('createDealershipPipelines', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create lead pipelines.');
    }

    public function editLeadPipeline(User $user, Dealer $dealer, LeadPipeline $pipeline): Response
    {
        if ((string) $pipeline->dealer_id !== (string) $dealer->id) {
            return Response::deny('This pipeline does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('editDealershipPipelines', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit lead pipelines.');
    }

    public function deleteLeadPipeline(User $user, Dealer $dealer, LeadPipeline $pipeline): Response
    {
        if ((string) $pipeline->dealer_id !== (string) $dealer->id) {
            return Response::deny('This pipeline does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('deleteDealershipPipelines', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete lead pipelines.');
    }

    public function showLeadStages(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('indexDealershipPipelineStages', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view lead stages.');
    }

    public function showQuotations(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('indexDealershipQuotations', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view dealer quotations.');
    }

    public function createQuotation(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('createDealershipQuotations', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create dealer quotations.');
    }

    public function editQuotation(User $user, Dealer $dealer, Quotation $quotation): Response
    {
        if ((string) $quotation->dealer_id !== (string) $dealer->id) {
            return Response::deny('This quotation does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('editDealershipQuotations', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit dealer quotations.');
    }

    public function deleteQuotation(User $user, Dealer $dealer, Quotation $quotation): Response
    {
        if ((string) $quotation->dealer_id !== (string) $dealer->id) {
            return Response::deny('This quotation does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('deleteDealershipQuotations', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete dealer quotations.');
    }

    public function createLeadStage(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('createDealershipPipelineStages', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create lead stages.');
    }

    public function editLeadStage(User $user, Dealer $dealer, LeadStage $stage): Response
    {
        $stage->loadMissing('pipeline:id,dealer_id');
        if ((string) $stage->pipeline?->dealer_id !== (string) $dealer->id) {
            return Response::deny('This stage does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('editDealershipPipelineStages', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit lead stages.');
    }

    public function deleteLeadStage(User $user, Dealer $dealer, LeadStage $stage): Response
    {
        $stage->loadMissing('pipeline:id,dealer_id');
        if ((string) $stage->pipeline?->dealer_id !== (string) $dealer->id) {
            return Response::deny('This stage does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('deleteDealershipPipelineStages', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete lead stages.');
    }

    public function showStockItem(User $user, Dealer $dealer, Stock $stock): Response
    {
        $stock->loadMissing('branch:id,dealer_id');
        if ((string) $stock->branch?->dealer_id !== (string) $dealer->id) {
            return Response::deny('This stock item does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('showDealershipStock', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view this stock item.');
    }

    public function createStock(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('createDealershipStock', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create dealer stock.');
    }

    public function editStock(User $user, Dealer $dealer, Stock $stock): Response
    {
        $stock->loadMissing('branch:id,dealer_id');
        if ((string) $stock->branch?->dealer_id !== (string) $dealer->id) {
            return Response::deny('This stock item does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('editDealershipStock', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit dealer stock.');
    }

    public function deleteStock(User $user, Dealer $dealer, Stock $stock): Response
    {
        $stock->loadMissing('branch:id,dealer_id');
        if ((string) $stock->branch?->dealer_id !== (string) $dealer->id) {
            return Response::deny('This stock item does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('deleteDealershipStock', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete dealer stock.');
    }

    public function changeStockStatusItem(User $user, Dealer $dealer, Stock $stock): Response
    {
        $stock->loadMissing('branch:id,dealer_id');
        if ((string) $stock->branch?->dealer_id !== (string) $dealer->id) {
            return Response::deny('This stock item does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('changeStockStatus', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to change stock status.');
    }

    public function showNotes(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('showNotes', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view dealer notes.');
    }

    public function showNotificationHistory(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('showDealershipNotificationHistory', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view dealer notification history.');
    }

    public function showSettings(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('canConfigureDealershipSettings', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view dealer settings.');
    }

    public function showBillings(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('showDealershipBillings', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view dealer billings.');
    }

    public function showAuditLog(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('showDealershipAuditLogs', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view dealer audit log.');
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('createDealerships', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create dealerships.');
    }

    public function update(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('editDealerships', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit dealerships.');
    }

    public function changeStatus(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('changeDealershipStatus', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to change dealership status.');
    }

    public function delete(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('deleteDealerships', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete dealerships.');
    }

    public function createBranch(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('editDealershipBranches', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create dealer branches.');
    }

    public function updateBranch(User $user, Dealer $dealer, DealerBranch $branch): Response
    {
        if ((string) $branch->dealer_id !== (string) $dealer->id) {
            return Response::deny('This branch does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('editDealershipBranches', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit dealer branches.');
    }

    public function deleteBranch(User $user, Dealer $dealer, DealerBranch $branch): Response
    {
        if ((string) $branch->dealer_id !== (string) $dealer->id) {
            return Response::deny('This branch does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('deleteDealershipBranches', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete dealer branches.');
    }

    public function updateSalesPerson(User $user, Dealer $dealer, DealerSalePerson $salesPerson): Response
    {
        $salesPerson->loadMissing('branch:id,dealer_id');
        if ((string) $salesPerson->branch?->dealer_id !== (string) $dealer->id) {
            return Response::deny('This sales person does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('editDealershipSalesPeople', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit dealer sales people.');
    }

    public function createSalesPerson(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('createDealershipSalesPeople', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create dealer sales people.');
    }

    public function deleteSalesPerson(User $user, Dealer $dealer, DealerSalePerson $salesPerson): Response
    {
        $salesPerson->loadMissing('branch:id,dealer_id');
        if ((string) $salesPerson->branch?->dealer_id !== (string) $dealer->id) {
            return Response::deny('This sales person does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('deleteDealershipSalesPeople', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete dealer sales people.');
    }

    public function updateDealerUser(User $user, Dealer $dealer, DealerUser $dealerUser): Response
    {
        if ((string) $dealerUser->dealer_id !== (string) $dealer->id) {
            return Response::deny('This user does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('editDealershipUsers', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit dealer users.');
    }

    public function createDealerUser(User $user, Dealer $dealer): Response
    {
        return $user->hasPermissionTo('createDealershipUsers', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create dealer users.');
    }

    public function deleteDealerUser(User $user, Dealer $dealer, DealerUser $dealerUser): Response
    {
        if ((string) $dealerUser->dealer_id !== (string) $dealer->id) {
            return Response::deny('This user does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('deleteDealershipUsers', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete dealer users.');
    }

    public function resetDealerUserPassword(User $user, Dealer $dealer, DealerUser $dealerUser): Response
    {
        if ((string) $dealerUser->dealer_id !== (string) $dealer->id) {
            return Response::deny('This user does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('resetDealershipUserPasswords', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to reset dealer user passwords.');
    }

    public function assignDealerUserPermissions(User $user, Dealer $dealer, DealerUser $dealerUser): Response
    {
        if ((string) $dealerUser->dealer_id !== (string) $dealer->id) {
            return Response::deny('This user does not belong to the selected dealer.');
        }

        return $user->hasPermissionTo('assignDealerPermisssions', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to assign dealer user permissions.');
    }
}
