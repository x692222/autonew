<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\System;
use App\Actions\Backoffice\GuardBackoffice\System\Users\CreateUserAction;
use App\Actions\Backoffice\GuardBackoffice\System\Users\SetUserActiveStatusAction;
use App\Actions\Backoffice\GuardBackoffice\System\Users\UpdateUserAction;
use App\Actions\Backoffice\Shared\Users\DeleteBackofficeUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\System\UsersManagement\CreateUsersManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\UsersManagement\DestroyUsersManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\UsersManagement\EditUsersManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\UsersManagement\IndexUsersManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\UsersManagement\ResetPasswordUsersManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\UsersManagement\StoreUsersManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\UsersManagement\ToggleUserStatusUsersManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\UsersManagement\UpdateUsersManagementRequest;
use App\Http\Resources\Backoffice\GuardBackoffice\System\UsersManagement\UserManagementIndexResource;
use App\Models\System\Role;
use App\Models\System\User;
use App\Support\Tables\DataTableColumnBuilder;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UsersManagementController extends Controller
{
    private string $publicTitle = 'User Management';

    public function index(IndexUsersManagementRequest $request): Response
    {
        $filters = $request->validated();

        $query = User::query()
            ->select(['id', 'firstname', 'lastname', 'email', 'is_active'])
            ->with(['roles:id,name'])
            ->filterSearch($filters['search'] ?? null, ['firstname', 'lastname', 'email']);

        $sortBy = $filters['sortBy'] ?? 'name';
        $descending = filter_var($filters['descending'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';

        switch ($sortBy) {
            case 'email':
                $query->orderBy('email', $descending);
                break;
            case 'status':
                $query->orderBy('is_active', $descending);
                break;
            case 'name':
            default:
                $query->orderBy('firstname', $descending)
                    ->orderBy('lastname', $descending);
                break;
        }

        $records = $query
            ->paginate((int) ($filters['rowsPerPage'] ?? 25))
            ->appends($filters);

        $records->setCollection(
            $records->getCollection()->map(
                fn (User $user) => (new UserManagementIndexResource($user))->toArray($request)
            )
        );

        $columns = DataTableColumnBuilder::make(
            keys: ['name', 'email', 'status', 'roles'],
            sortableKeys: ['name', 'email', 'status']
        );

        return Inertia::render('GuardBackoffice/System/UserManagement/Index', [
            'publicTitle' => $this->publicTitle,
            'filters' => $filters,
            'columns' => $columns,
            'records' => $records,
        ]);
    }

    public function create(CreateUsersManagementRequest $request): Response
    {
        $roles = Role::query()
            ->where('guard_name', 'backoffice')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        return Inertia::render('GuardBackoffice/System/UserManagement/Create', [
            'publicTitle' => $this->publicTitle,
            'roles' => $roles,
        ]);
    }

    public function store(StoreUsersManagementRequest $request, CreateUserAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return redirect()
            ->route('backoffice.system.user-management.users.index', $request->query())
            ->with('success', 'User registered.');
    }

    public function edit(EditUsersManagementRequest $request, User $user): Response
    {
        $roles = Role::query()
            ->where('guard_name', 'backoffice')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        $user->load('roles:id,name');

        return Inertia::render('GuardBackoffice/System/UserManagement/Edit', [
            'publicTitle' => $this->publicTitle,
            'roles' => $roles,
            'data' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'role' => $user->roles->pluck('name')->first(),
            ],
        ]);
    }

    public function update(UpdateUsersManagementRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $action->execute($user, $request->validated());

        return redirect()
            ->route('backoffice.system.user-management.users.index', $request->query())
            ->with('success', 'User updated.');
    }

    public function destroy(DestroyUsersManagementRequest $request, User $user, DeleteBackofficeUserAction $action): RedirectResponse
    {
        // @todo Revisit destroy behavior and potential soft-delete restoration UX.
        $action->execute($user);

        return redirect()
            ->route('backoffice.system.user-management.users.index', $request->query())
            ->with('success', 'User deleted.');
    }

    public function activate(ToggleUserStatusUsersManagementRequest $request, User $user, SetUserActiveStatusAction $action): RedirectResponse
    {
        $action->execute($user, true);

        return back()->with('success', 'User activated.');
    }

    public function deactivate(ToggleUserStatusUsersManagementRequest $request, User $user, SetUserActiveStatusAction $action): RedirectResponse
    {
        $action->execute($user, false);

        return back()->with('success', 'User deactivated.');
    }

    public function resetPassword(ResetPasswordUsersManagementRequest $request, User $user): RedirectResponse
    {
        if ($user->is_active === false) {
            return back()->with('error', 'User is inactive - unable to reset password');
        }

        $status = Password::broker('users')->sendResetLink([
            'email' => $user->email,
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', __($status));
        }

        return back()->with('error', __($status));
    }
}
