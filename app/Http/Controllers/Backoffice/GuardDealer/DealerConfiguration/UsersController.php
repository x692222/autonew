<?php

namespace App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Users\CreateDealerConfigurationUsersRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Users\DestroyDealerConfigurationUsersRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Users\EditDealerConfigurationUsersRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Users\IndexDealerConfigurationUsersRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Users\ResetPasswordDealerConfigurationUsersRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Users\StoreDealerConfigurationUsersRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Users\UpdateDealerConfigurationUsersRequest;
use App\Models\Dealer\DealerUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UsersController extends Controller
{
    public function index(IndexDealerConfigurationUsersRequest $request): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexUsers', $dealer);

        $filters = $request->validated();
        $query = $dealer->users()
            ->select(['id', 'dealer_id', 'firstname', 'lastname', 'email', 'is_active'])
            ->withCount('notes')
            ->filterSearch($filters['search'] ?? null, ['firstname', 'lastname', 'email']);

        $sortBy = $filters['sortBy'] ?? 'name';
        $direction = filter_var($filters['descending'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';
        match ($sortBy) {
            'email' => $query->orderBy('email', $direction),
            'status' => $query->orderBy('is_active', $direction),
            'notes_count' => $query->orderBy('notes_count', $direction),
            default => $query->orderBy('firstname', $direction)->orderBy('lastname', $direction),
        };

        $records = $query->paginate((int) ($filters['rowsPerPage'] ?? 25))->appends($filters);
        $records->setCollection($records->getCollection()->map(function (DealerUser $dealerUser) use ($actor, $dealer) {
            return [
                'id' => $dealerUser->id,
                'name' => trim((string) $dealerUser->firstname . ' ' . (string) $dealerUser->lastname),
                'email' => $dealerUser->email ?? '-',
                'is_active' => (bool) $dealerUser->is_active,
                'status' => $dealerUser->is_active ? 'Active' : 'Inactive',
                'notes_count' => (int) ($dealerUser->notes_count ?? 0),
                'can' => [
                    'edit' => Gate::forUser($actor)->inspect('dealerConfigurationEditUser', $dealerUser)->allowed(),
                    'delete' => Gate::forUser($actor)->inspect('dealerConfigurationDeleteUser', $dealerUser)->allowed(),
                    'reset_password' => Gate::forUser($actor)->inspect('dealerConfigurationResetUserPassword', $dealerUser)->allowed(),
                    'assign_permissions' => Gate::forUser($actor)->inspect('dealerConfigurationAssignUserPermissions', $dealerUser)->allowed(),
                    'show_notes' => Gate::forUser($actor)->inspect('dealerConfigurationShowNotes', $dealer)->allowed(),
                ],
            ];
        }));

        $columns = collect(['name', 'email', 'status'])
            ->map(fn (string $key) => [
                'name' => $key,
                'label' => Str::headline($key),
                'sortable' => in_array($key, ['name', 'email', 'status'], true),
                'align' => Str::endsWith($key, '_count') ? 'right' : 'left',
                'field' => $key,
                'numeric' => Str::endsWith($key, '_count'),
            ])->values()->all();

        return Inertia::render('GuardDealer/DealerConfiguration/Users/Index', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'filters' => $filters,
            'columns' => $columns,
            'records' => $records,
        ]);
    }

    public function edit(EditDealerConfigurationUsersRequest $request, DealerUser $dealerUser): Response
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditUser', $dealerUser);

        return Inertia::render('GuardDealer/DealerConfiguration/Users/Edit', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $actor->dealer->id, 'name' => $actor->dealer->name],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.users.index')),
            'data' => [
                'id' => $dealerUser->id,
                'firstname' => $dealerUser->firstname,
                'lastname' => $dealerUser->lastname,
                'email' => $dealerUser->email,
            ],
        ]);
    }

    public function create(CreateDealerConfigurationUsersRequest $request): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationCreateUser', $dealer);

        return Inertia::render('GuardDealer/DealerConfiguration/Users/Create', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.users.index')),
        ]);
    }

    public function store(StoreDealerConfigurationUsersRequest $request): RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationCreateUser', $dealer);

        $data = $request->safe()->except(['return_to']);
        $dealerUser = $dealer->users()->create($data);
        Password::broker('dealers')->sendResetLink(['email' => $dealerUser->email]);

        return redirect($request->input('return_to', route('backoffice.dealer-configuration.users.index')))
            ->with('success', 'Dealer user created.');
    }

    public function update(UpdateDealerConfigurationUsersRequest $request, DealerUser $dealerUser): RedirectResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditUser', $dealerUser);

        $data = $request->safe()->except(['return_to']);
        $dealerUser->update($data);

        return redirect($request->input('return_to', route('backoffice.dealer-configuration.users.index')))
            ->with('success', 'Dealer user updated.');
    }

    public function destroy(DestroyDealerConfigurationUsersRequest $request, DealerUser $dealerUser): RedirectResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationDeleteUser', $dealerUser);

        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $dealerUser->delete();

        return back()->with('success', 'Dealer user deleted.');
    }

    public function resetPassword(ResetPasswordDealerConfigurationUsersRequest $request, DealerUser $dealerUser): RedirectResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationResetUserPassword', $dealerUser);

        if ($dealerUser->is_active === false) {
            return back()->with('error', 'User is inactive - unable to reset password.');
        }

        $status = Password::broker('dealers')->sendResetLink(['email' => $dealerUser->email]);
        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', __($status));
        }

        return back()->with('error', __($status));
    }
}
