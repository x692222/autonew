<?php

namespace App\Http\Controllers\Backoffice\DealerManagement\Dealers;

use App\Actions\DealerManagement\Dealers\AssignDealerUserPermissionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\Users\EditDealerUserPermissionsRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\Users\UpdateDealerUserPermissionsRequest;
use App\Http\Resources\Backoffice\System\AccessManagement\AccessManagementPermissionResource;
use App\Http\Resources\Backoffice\System\AccessManagement\AccessManagementUserResource;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use App\Models\System\Permission;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserPermissionsController extends Controller
{
    private string $publicTitle = 'Access Management';

    public function edit(
        EditDealerUserPermissionsRequest $request,
        Dealer $dealer,
        DealerUser $dealerUser
    ): Response {
        $dealerUser->load('permissions:id,name');

        $permissions = Permission::query()
            ->select(['id', 'name'])
            ->where('guard_name', 'dealer')
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission) => (new AccessManagementPermissionResource($permission))->toArray($request))
            ->values()
            ->all();

        return Inertia::render('System/AccessManagement/Edit', [
            'publicTitle' => $this->publicTitle,
            'data' => (new AccessManagementUserResource($dealerUser))->toArray($request),
            'permissions' => $permissions,
            'updateRoute' => route('backoffice.dealer-management.dealers.users.permissions.update', [$dealer->id, $dealerUser->id]),
            'cancelRoute' => $request->input('return_to', route('backoffice.dealer-management.dealers.users', $dealer->id)),
        ]);
    }

    public function update(
        UpdateDealerUserPermissionsRequest $request,
        Dealer $dealer,
        DealerUser $dealerUser,
        AssignDealerUserPermissionsAction $action
    ): RedirectResponse {
        $payload = $request->validated();
        $action->execute($dealerUser, $payload['permissions'] ?? []);

        return back()->with('success', 'Permissions updated.');
    }
}
