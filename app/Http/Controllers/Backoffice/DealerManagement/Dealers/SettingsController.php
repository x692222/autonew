<?php

namespace App\Http\Controllers\Backoffice\DealerManagement\Dealers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\ShowDealerTabRequest;
use App\Models\Dealer\Dealer;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function show(ShowDealerTabRequest $request, Dealer $dealer): Response
    {
        return Inertia::render('DealerManagement/Dealers/Tabs/Settings', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'settings',
        ]);
    }
}
