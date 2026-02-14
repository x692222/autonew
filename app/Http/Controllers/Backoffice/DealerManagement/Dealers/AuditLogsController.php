<?php

namespace App\Http\Controllers\Backoffice\DealerManagement\Dealers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\ShowDealerTabRequest;
use App\Models\Dealer\Dealer;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogsController extends Controller
{
    public function show(ShowDealerTabRequest $request, Dealer $dealer): Response
    {
        return Inertia::render('DealerManagement/Dealers/Tabs/AuditLog', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'audit-log',
        ]);
    }
}
