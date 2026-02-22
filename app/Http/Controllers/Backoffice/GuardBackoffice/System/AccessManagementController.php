<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\System;
use App\Actions\Backoffice\GuardBackoffice\System\Users\AssignUserPermissionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\System\AccessManagement\EditAccessManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\AccessManagement\UpdateAccessManagementRequest;
use App\Http\Resources\Backoffice\GuardBackoffice\System\AccessManagement\AccessManagementPermissionResource;
use App\Http\Resources\Backoffice\GuardBackoffice\System\AccessManagement\AccessManagementUserResource;
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
            ->select(['id', 'name', 'group'])
            ->where('guard_name', 'backoffice')
            ->orderByRaw('CASE WHEN `group` IS NULL THEN 1 ELSE 0 END')
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission) => (new AccessManagementPermissionResource($permission))->toArray($request))
            ->values()
            ->all();

        return Inertia::render('GuardBackoffice/System/AccessManagement/Edit', [
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
