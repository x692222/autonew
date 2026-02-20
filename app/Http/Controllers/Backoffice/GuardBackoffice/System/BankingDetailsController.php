<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\System;

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
        Gate::authorize('viewAny', BankingDetail::class);

        $filters = $request->validate($this->validationRules->index());

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

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', BankingDetail::class);

        $data = $request->validate($this->validationRules->upsert());

        BankingDetail::query()->create([
            'dealer_id' => null,
            'label' => $data['label'],
            'institution' => $data['institution'],
            'details' => $data['details'],
        ]);

        return back()->with('success', 'Banking detail created.');
    }

    public function update(Request $request, BankingDetail $bankingDetail): RedirectResponse
    {
        Gate::authorize('update', $bankingDetail);

        $data = $request->validate($this->validationRules->upsert());

        $bankingDetail->update($data);

        return back()->with('success', 'Banking detail updated.');
    }

    public function destroy(Request $request, BankingDetail $bankingDetail): RedirectResponse
    {
        Gate::authorize('delete', $bankingDetail);

        if ($bankingDetail->payments()->exists()) {
            return back()->with('error', 'This banking detail is linked to payments and cannot be deleted.');
        }

        $bankingDetail->delete();

        return back()->with('success', 'Banking detail deleted.');
    }
}
