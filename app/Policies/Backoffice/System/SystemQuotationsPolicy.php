<?php

namespace App\Policies\Backoffice\System;

use App\Models\Quotation\Quotation;
use App\Models\System\User;
use Illuminate\Auth\Access\Response;

class SystemQuotationsPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('indexSystemQuotations', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view system quotations.');
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('createSystemQuotations', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create system quotations.');
    }

    public function update(User $user, Quotation $quotation): Response
    {
        if ($quotation->dealer_id !== null) {
            return Response::deny('This is not a system quotation.');
        }

        return $user->hasPermissionTo('editSystemQuotations', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit system quotations.');
    }

    public function delete(User $user, Quotation $quotation): Response
    {
        if ($quotation->dealer_id !== null) {
            return Response::deny('This is not a system quotation.');
        }

        return $user->hasPermissionTo('deleteSystemQuotations', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete system quotations.');
    }
}

