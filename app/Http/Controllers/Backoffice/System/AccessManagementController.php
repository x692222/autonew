<?php

namespace App\Http\Controllers\Backoffice\System;

use App\Actions\System\Users\AssignUserPermissionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\System\AccessManagement\EditAccessManagementRequest;
use App\Http\Requests\Backoffice\System\AccessManagement\UpdateAccessManagementRequest;
use App\Http\Resources\Backoffice\System\AccessManagement\AccessManagementPermissionResource;
use App\Http\Resources\Backoffice\System\AccessManagement\AccessManagementUserResource;
use App\Models\System\Permission;
use App\Models\System\User;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AccessManagementController extends Controller
{
    private string $publicTitle = 'Access Management';

    public function edit(EditAccessManagementRequest $request, User $user): Response
    {
        $user->load('permissions:id,name');

        $permissions = Permission::query()
            ->select(['id', 'name'])
            ->where('guard_name', 'backoffice')
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission) => (new AccessManagementPermissionResource($permission))->toArray($request))
            ->values()
            ->all();

        return Inertia::render('System/AccessManagement/Edit', [
            'publicTitle' => $this->publicTitle,
            'data' => (new AccessManagementUserResource($user))->toArray($request),
            'permissions' => $permissions,
        ]);
    }

    public function update(
        UpdateAccessManagementRequest $request,
        User $user,
        AssignUserPermissionsAction $action
    ): RedirectResponse {
        $payload = $request->validated();
        $action->execute($user, $payload['permissions'] ?? []);

        return back()->with('success', 'Permissions updated.');
    }
}
