<?php

namespace App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\EditDealershipRequest;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EditDealershipController extends Controller
{
    public function show(EditDealershipRequest $request): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;

        Gate::forUser($actor)->authorize('dealerConfigurationEditDealership', $dealer);

        return Inertia::render('GuardDealer/DealerConfiguration/EditDealership', [
            'publicTitle' => 'Configuration',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
        ]);
    }
}
