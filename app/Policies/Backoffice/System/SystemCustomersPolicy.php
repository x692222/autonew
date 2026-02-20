<?php

namespace App\Policies\Backoffice\System;

use App\Models\Quotation\Customer;
use App\Models\System\User;
use Illuminate\Auth\Access\Response;

class SystemCustomersPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('indexSystemCustomers', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view system customers.');
    }

    public function view(User $user, Customer $customer): Response
    {
        if ($customer->dealer_id !== null) {
            return Response::deny('This is not a system customer.');
        }

        return $user->hasPermissionTo('editSystemCustomers', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view system customers.');
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('createSystemCustomers', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create system customers.');
    }

    public function update(User $user, Customer $customer): Response
    {
        if ($customer->dealer_id !== null) {
            return Response::deny('This is not a system customer.');
        }

        return $user->hasPermissionTo('editSystemCustomers', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit system customers.');
    }

    public function delete(User $user, Customer $customer): Response
    {
        if ($customer->dealer_id !== null) {
            return Response::deny('This is not a system customer.');
        }

        return $user->hasPermissionTo('deleteSystemCustomers', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete system customers.');
    }
}
