<?php

namespace App\Http\Controllers\Backoffice\DealerConfiguration;

use App\Actions\DealerManagement\Dealers\AssignDealerUserPermissionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\DealerConfiguration\Users\EditDealerConfigurationUserPermissionsRequest;
use App\Http\Requests\Backoffice\DealerConfiguration\Users\UpdateDealerConfigurationUserPermissionsRequest;
use App\Http\Resources\Backoffice\System\AccessManagement\AccessManagementPermissionResource;
use App\Http\Resources\Backoffice\System\AccessManagement\AccessManagementUserResource;
use App\Models\Dealer\DealerUser;
use App\Models\System\Permission;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserPermissionsController extends Controller
{
    private string $publicTitle = 'Access Management';

    public function edit(
        EditDealerConfigurationUserPermissionsRequest $request,
        DealerUser $dealerUser
    ): Response {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationAssignUserPermissions', $dealerUser);

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
            'updateRoute' => route('backoffice.dealer-configuration.users.permissions.update', $dealerUser->id),
            'cancelRoute' => $request->input('return_to', route('backoffice.dealer-configuration.users.index')),
        ]);
    }

    public function update(
        UpdateDealerConfigurationUserPermissionsRequest $request,
        DealerUser $dealerUser,
        AssignDealerUserPermissionsAction $action
    ): RedirectResponse {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationAssignUserPermissions', $dealerUser);

        $payload = $request->validated();
        $action->execute($dealerUser, $payload['permissions'] ?? []);

        return back()->with('success', 'Permissions updated.');
    }
}
