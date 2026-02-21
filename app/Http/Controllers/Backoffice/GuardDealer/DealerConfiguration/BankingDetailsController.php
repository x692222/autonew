<?php

namespace App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration;

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
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexBankingDetails', $dealer);

        $filters = $request->validated();

        $records = $this->indexService->paginate($filters, $dealer->id);

        $records->through(fn (BankingDetail $row) => $this->indexService->toArray($row, fn (BankingDetail $bankingDetail) => [
                'edit' => Gate::forUser($actor)->inspect('dealerConfigurationEditBankingDetail', $bankingDetail)->allowed(),
                'delete' => Gate::forUser($actor)->inspect('dealerConfigurationDeleteBankingDetail', $bankingDetail)->allowed(),
            ]));

        return Inertia::render('Shared/BankingDetails/Index', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'context' => ['mode' => 'dealer'],
            'records' => $records,
            'filters' => $filters,
            'createRoute' => route('backoffice.dealer-configuration.banking-details.store'),
            'updateRouteName' => 'backoffice.dealer-configuration.banking-details.update',
            'deleteRouteName' => 'backoffice.dealer-configuration.banking-details.destroy',
            'canCreate' => Gate::forUser($actor)->inspect('dealerConfigurationCreateBankingDetail', $dealer)->allowed(),
        ]);
    }

    public function store(
        UpsertBankingDetailsRequest $request,
        CreateBankingDetailAction $createBankingDetailAction
    ): RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationCreateBankingDetail', $dealer);

        $data = $request->validated();

        $createBankingDetailAction->execute([
            'dealer_id' => $dealer->id,
            'bank' => $data['bank'],
            'account_holder' => $data['account_holder'],
            'account_number' => $data['account_number'],
            'branch_name' => $data['branch_name'] ?? null,
            'branch_code' => $data['branch_code'] ?? null,
            'swift_code' => $data['swift_code'] ?? null,
            'other_details' => $data['other_details'],
        ], $dealer);

        return back()->with('success', 'Banking detail created.');
    }

    public function update(
        UpsertBankingDetailsRequest $request,
        BankingDetail $bankingDetail,
        UpdateBankingDetailAction $updateBankingDetailAction
    ): RedirectResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditBankingDetail', $bankingDetail);

        $data = $request->validated();

        $updateBankingDetailAction->execute($bankingDetail, $data, $actor->dealer);

        return back()->with('success', 'Banking detail updated.');
    }

    public function destroy(
        Request $request,
        BankingDetail $bankingDetail,
        DeleteBankingDetailAction $deleteBankingDetailAction
    ): RedirectResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationDeleteBankingDetail', $bankingDetail);

        if ($bankingDetail->payments()->exists()) {
            return back()->with('error', 'These banking details are linked to payments and cannot be deleted.');
        }

        $deleteBankingDetailAction->execute($bankingDetail, $actor->dealer);

        return back()->with('success', 'Banking detail deleted.');
    }
}
