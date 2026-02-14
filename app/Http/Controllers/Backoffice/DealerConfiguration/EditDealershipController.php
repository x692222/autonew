<?php

namespace App\Http\Controllers\Backoffice\DealerConfiguration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\DealerConfiguration\EditDealershipRequest;
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

        return Inertia::render('DealerConfiguration/EditDealership', [
            'publicTitle' => 'Configuration',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
        ]);
    }
}
