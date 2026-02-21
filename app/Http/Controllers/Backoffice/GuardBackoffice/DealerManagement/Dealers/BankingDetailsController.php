<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers;

use App\Actions\Backoffice\Shared\BankingDetails\CreateBankingDetailAction;
use App\Actions\Backoffice\Shared\BankingDetails\DeleteBankingDetailAction;
use App\Actions\Backoffice\Shared\BankingDetails\UpdateBankingDetailAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Shared\BankingDetails\IndexBankingDetailsRequest;
use App\Http\Requests\Backoffice\Shared\BankingDetails\UpsertBankingDetailsRequest;
use App\Models\Billing\BankingDetail;
use App\Models\Dealer\Dealer;
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

    public function index(IndexBankingDetailsRequest $request, Dealer $dealer): Response
    {
        Gate::authorize('showBankingDetails', $dealer);

        $filters = $request->validated();

        $records = $this->indexService->paginate($filters, $dealer->id);

        $records->through(fn (BankingDetail $row) => $this->indexService->toArray($row, fn (BankingDetail $bankingDetail) => [
                'edit' => Gate::inspect('editBankingDetail', [$dealer, $bankingDetail])->allowed(),
                'delete' => Gate::inspect('deleteBankingDetail', [$dealer, $bankingDetail])->allowed(),
            ]));

        return Inertia::render('Shared/BankingDetails/Index', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'pageTab' => 'banking-details',
            'context' => ['mode' => 'dealer-backoffice'],
            'records' => $records,
            'filters' => $filters,
            'createRoute' => route('backoffice.dealer-management.dealers.banking-details.store', $dealer),
            'updateRouteName' => 'backoffice.dealer-management.dealers.banking-details.update',
            'deleteRouteName' => 'backoffice.dealer-management.dealers.banking-details.destroy',
            'canCreate' => Gate::inspect('createBankingDetail', $dealer)->allowed(),
        ]);
    }

    public function store(
        UpsertBankingDetailsRequest $request,
        Dealer $dealer,
        CreateBankingDetailAction $createBankingDetailAction
    ): RedirectResponse
    {
        Gate::authorize('createBankingDetail', $dealer);

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
        Dealer $dealer,
        BankingDetail $bankingDetail,
        UpdateBankingDetailAction $updateBankingDetailAction
    ): RedirectResponse
    {
        Gate::authorize('editBankingDetail', [$dealer, $bankingDetail]);

        $data = $request->validated();

        $updateBankingDetailAction->execute($bankingDetail, $data, $dealer);

        return back()->with('success', 'Banking detail updated.');
    }

    public function destroy(
        Request $request,
        Dealer $dealer,
        BankingDetail $bankingDetail,
        DeleteBankingDetailAction $deleteBankingDetailAction
    ): RedirectResponse
    {
        Gate::authorize('deleteBankingDetail', [$dealer, $bankingDetail]);

        if ($bankingDetail->payments()->exists()) {
            return back()->with('error', 'These banking details are linked to payments and cannot be deleted.');
        }

        $deleteBankingDetailAction->execute($bankingDetail, $dealer);

        return back()->with('success', 'Banking detail deleted.');
    }
}
