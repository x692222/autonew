<?php

namespace App\Policies\Backoffice\DealerManagement;

use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerSalePerson;
use App\Models\Dealer\DealerUser;
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
        return $user->hasPermissionTo('showDealershipSettings', 'backoffice')
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

        return $user->hasPermissionTo('assignPermissions', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to assign dealer user permissions.');
    }
}
