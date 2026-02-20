<?php

namespace App\Policies\Backoffice\System;

use App\Models\Payments\Payment;
use App\Models\System\User;
use Illuminate\Auth\Access\Response;

class SystemPaymentsPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('indexSystemPayments', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view system payments.');
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('createSystemPayments', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create system payments.');
    }

    public function view(User $user, Payment $payment): Response
    {
        if ($payment->dealer_id !== null) {
            return Response::deny('This is not a system payment.');
        }

        return $user->hasPermissionTo('viewSystemPayments', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view this system payment.');
    }

    public function update(User $user, Payment $payment): Response
    {
        if ($payment->dealer_id !== null) {
            return Response::deny('This is not a system payment.');
        }

        return $user->hasPermissionTo('editSystemPayments', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit system payments.');
    }

    public function delete(User $user, Payment $payment): Response
    {
        if ($payment->dealer_id !== null) {
            return Response::deny('This is not a system payment.');
        }

        return $user->hasPermissionTo('deleteSystemPayments', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete system payments.');
    }
}
