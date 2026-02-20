<?php

namespace App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration;

use App\Http\Controllers\Controller;
use App\Models\Billing\BankingDetail;
use App\Support\BankingDetails\BankingDetailsIndexService;
use App\Support\BankingDetails\BankingDetailValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BankingDetailsController extends Controller
{
    public function __construct(
        private readonly BankingDetailValidationRules $validationRules,
        private readonly BankingDetailsIndexService $indexService,
    ) {
    }

    public function index(Request $request): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexBankingDetails', $dealer);

        $filters = $request->validate($this->validationRules->index());

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

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationCreateBankingDetail', $dealer);

        $data = $request->validate($this->validationRules->upsert());

        BankingDetail::query()->create([
            'dealer_id' => $dealer->id,
            'label' => $data['label'],
            'institution' => $data['institution'],
            'details' => $data['details'],
        ]);

        return back()->with('success', 'Banking detail created.');
    }

    public function update(Request $request, BankingDetail $bankingDetail): RedirectResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditBankingDetail', $bankingDetail);

        $data = $request->validate($this->validationRules->upsert());

        $bankingDetail->update($data);

        return back()->with('success', 'Banking detail updated.');
    }

    public function destroy(Request $request, BankingDetail $bankingDetail): RedirectResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationDeleteBankingDetail', $bankingDetail);

        if ($bankingDetail->payments()->exists()) {
            return back()->with('error', 'This banking detail is linked to payments and cannot be deleted.');
        }

        $bankingDetail->delete();

        return back()->with('success', 'Banking detail deleted.');
    }
}
