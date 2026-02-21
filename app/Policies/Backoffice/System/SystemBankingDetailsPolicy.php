<?php

namespace App\Policies\Backoffice\System;

use App\Models\Billing\BankingDetail;
use App\Models\System\User;
use Illuminate\Auth\Access\Response;

class SystemBankingDetailsPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('indexSystemBankingDetails', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view system banking details.');
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('createSystemBankingDetails', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create system banking details.');
    }

    public function update(User $user, BankingDetail $bankingDetail): Response
    {
        if ($bankingDetail->dealer_id !== null) {
            return Response::deny('This is not a system banking details record.');
        }

        return $user->hasPermissionTo('editSystemBankingDetails', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit system banking details.');
    }

    public function delete(User $user, BankingDetail $bankingDetail): Response
    {
        if ($bankingDetail->dealer_id !== null) {
            return Response::deny('This is not a system banking details record.');
        }

        if ($bankingDetail->payments()->exists()) {
            return Response::deny('These banking details are linked to payments and cannot be deleted.');
        }

        return $user->hasPermissionTo('deleteSystemBankingDetails', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete system banking details.');
    }
}
