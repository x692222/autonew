<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Settings\ShowDealerSettingsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Settings\UpdateDealerSettingsRequest;
use App\Http\Resources\Backoffice\Shared\Settings\ConfigurationFieldResource;
use App\Models\Dealer\Dealer;
use App\Support\Options\StockOptions;
use App\Support\Settings\ConfigurationCatalog;
use App\Support\Settings\ConfigurationManager;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SettingsController extends Controller
{
    public function __construct(
        private readonly ConfigurationManager $configurationManager,
        private readonly ConfigurationCatalog $catalog
    ) {
    }

    public function show(ShowDealerSettingsRequest $request, Dealer $dealer): Response
    {
        $rows = $this->configurationManager->syncDealerDefaults($dealer);

        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Tabs/Settings', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'settings',
            'settings' => ConfigurationFieldResource::collection($rows)->resolve(),
            'categoryOptions' => $this->catalog->categoryOptions(),
            'timezoneOptions' => $this->catalog->timezoneOptions(),
            'stockTypeOptions' => StockOptions::types(withAll: false)->resolve(),
            'updateRoute' => route('backoffice.dealer-management.dealers.settings.update', $dealer),
            'canUpdate' => true,
        ]);
    }

    public function update(UpdateDealerSettingsRequest $request, Dealer $dealer): RedirectResponse
    {
        $this->configurationManager->syncDealerDefaults($dealer);
        $this->configurationManager->updateDealerValues(
            dealer: $dealer,
            settings: (array) $request->validated('settings'),
            includeBackofficeOnly: true
        );

        return back()->with('success', 'Dealer settings updated.');
    }
}
