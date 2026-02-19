<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\ShowDealerTabRequest;
use App\Models\Dealer\Dealer;
use Inertia\Inertia;
use Inertia\Response;

class NotificationHistoriesController extends Controller
{
    public function show(ShowDealerTabRequest $request, Dealer $dealer): Response
    {
        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Tabs/NotificationHistory', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'notification-history',
        ]);
    }
}
