<?php

namespace App\Policies\Backoffice\System;

use App\Models\Invoice\Invoice;
use App\Models\System\User;
use Illuminate\Auth\Access\Response;

class SystemInvoicesPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('indexSystemInvoices', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view system invoices.');
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('createSystemInvoices', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create system invoices.');
    }

    public function update(User $user, Invoice $invoice): Response
    {
        if ($invoice->dealer_id !== null) {
            return Response::deny('This is not a system invoice.');
        }

        return $user->hasPermissionTo('editSystemInvoices', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit system invoices.');
    }

    public function delete(User $user, Invoice $invoice): Response
    {
        if ($invoice->dealer_id !== null) {
            return Response::deny('This is not a system invoice.');
        }

        return $user->hasPermissionTo('deleteSystemInvoices', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete system invoices.');
    }
}

