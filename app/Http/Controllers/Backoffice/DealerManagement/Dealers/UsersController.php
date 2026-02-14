<?php

namespace App\Http\Controllers\Backoffice\DealerManagement\Dealers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\Users\DestroyDealerUsersRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\Users\EditDealerUsersRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\Users\IndexDealerUsersRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\Users\ResetPasswordDealerUsersRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\Users\UpdateDealerUsersRequest;
use App\Http\Resources\Backoffice\DealerManagement\Dealers\Users\DealerUsersIndexResource;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UsersController extends Controller
{
    public function show(IndexDealerUsersRequest $request, Dealer $dealer): Response
    {
        $filters = $request->validated();

        $query = $dealer->users()
            ->select(['id', 'dealer_id', 'firstname', 'lastname', 'email', 'is_active'])
            ->with('dealer:id,name')
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

        $records = $query
            ->paginate((int) ($filters['rowsPerPage'] ?? 25))
            ->appends($filters);

        $records->setCollection(
            $records->getCollection()->map(
                fn (DealerUser $dealerUser) => (new DealerUsersIndexResource($dealerUser))->toArray($request)
            )
        );

        $columns = collect([
            'name',
            'email',
            'status',
            'notes_count',
        ])->map(fn (string $key) => [
            'name' => $key,
            'label' => Str::headline($key),
            'sortable' => in_array($key, ['name', 'email', 'status', 'notes_count'], true),
            'align' => Str::endsWith($key, '_count') ? 'right' : 'left',
            'field' => $key,
            'numeric' => Str::endsWith($key, '_count'),
        ])->values()->all();

        return Inertia::render('DealerManagement/Dealers/Tabs/Users', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'users',
            'filters' => $filters,
            'columns' => $columns,
            'records' => $records,
        ]);
    }

    public function edit(EditDealerUsersRequest $request, Dealer $dealer, DealerUser $dealerUser): Response
    {
        return Inertia::render('DealerManagement/Dealers/Users/Edit', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'users',
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.users', $dealer->id)),
            'data' => [
                'id' => $dealerUser->id,
                'firstname' => $dealerUser->firstname,
                'lastname' => $dealerUser->lastname,
                'email' => $dealerUser->email,
            ],
        ]);
    }

    public function update(UpdateDealerUsersRequest $request, Dealer $dealer, DealerUser $dealerUser): RedirectResponse
    {
        $data = $request->safe()->except(['return_to']);
        $dealerUser->update($data);

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.users', $dealer->id)))
            ->with('success', 'Dealer user updated.');
    }

    public function destroy(DestroyDealerUsersRequest $request, Dealer $dealer, DealerUser $dealerUser): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $dealerUser->delete();

        return back()->with('success', 'Dealer user deleted.');
    }

    public function resetPassword(ResetPasswordDealerUsersRequest $request, Dealer $dealer, DealerUser $dealerUser): RedirectResponse
    {
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
