<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\System;

use App\Actions\Backoffice\Shared\BankingDetails\CreateBankingDetailAction;
use App\Actions\Backoffice\Shared\BankingDetails\DeleteBankingDetailAction;
use App\Actions\Backoffice\Shared\BankingDetails\UpdateBankingDetailAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Shared\BankingDetails\IndexBankingDetailsRequest;
use App\Http\Requests\Backoffice\Shared\BankingDetails\UpsertBankingDetailsRequest;
use App\Models\Billing\BankingDetail;
use App\Support\BankingDetails\BankingDetailsIndexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BankingDetailsController extends Controller
{
    public function __construct(
        private readonly BankingDetailsIndexService $indexService,
    ) {
    }

    public function index(IndexBankingDetailsRequest $request): Response
    {
        Gate::authorize('viewAny', BankingDetail::class);

        $filters = $request->validated();

        $records = $this->indexService->paginate($filters);

        $records->through(fn (BankingDetail $row) => $this->indexService->toArray($row, fn (BankingDetail $bankingDetail) => [
                'edit' => Gate::inspect('update', $bankingDetail)->allowed(),
                'delete' => Gate::inspect('delete', $bankingDetail)->allowed(),
            ]));

        return Inertia::render('Shared/BankingDetails/Index', [
            'publicTitle' => 'Banking Details',
            'context' => ['mode' => 'system'],
            'records' => $records,
            'filters' => $filters,
            'createRoute' => route('backoffice.system.banking-details.store'),
            'updateRouteName' => 'backoffice.system.banking-details.update',
            'deleteRouteName' => 'backoffice.system.banking-details.destroy',
            'canCreate' => Gate::inspect('create', BankingDetail::class)->allowed(),
        ]);
    }

    public function store(
        UpsertBankingDetailsRequest $request,
        CreateBankingDetailAction $createBankingDetailAction
    ): RedirectResponse
    {
        Gate::authorize('create', BankingDetail::class);

        $data = $request->validated();

        $createBankingDetailAction->execute([
            'dealer_id' => null,
            'bank' => $data['bank'],
            'account_holder' => $data['account_holder'],
            'account_number' => $data['account_number'],
            'branch_name' => $data['branch_name'] ?? null,
            'branch_code' => $data['branch_code'] ?? null,
            'swift_code' => $data['swift_code'] ?? null,
            'other_details' => $data['other_details'],
        ]);

        return back()->with('success', 'Banking detail created.');
    }

    public function update(
        UpsertBankingDetailsRequest $request,
        BankingDetail $bankingDetail,
        UpdateBankingDetailAction $updateBankingDetailAction
    ): RedirectResponse
    {
        Gate::authorize('update', $bankingDetail);

        $data = $request->validated();

        $updateBankingDetailAction->execute($bankingDetail, $data);

        return back()->with('success', 'Banking detail updated.');
    }

    public function destroy(
        Request $request,
        BankingDetail $bankingDetail,
        DeleteBankingDetailAction $deleteBankingDetailAction
    ): RedirectResponse
    {
        Gate::authorize('delete', $bankingDetail);

        if ($bankingDetail->payments()->exists()) {
            return back()->with('error', 'These banking details are linked to payments and cannot be deleted.');
        }

        $deleteBankingDetailAction->execute($bankingDetail);

        return back()->with('success', 'Banking detail deleted.');
    }
}
