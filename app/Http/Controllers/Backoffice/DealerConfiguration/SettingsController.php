<?php

namespace App\Http\Controllers\Backoffice\DealerConfiguration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\DealerConfiguration\Settings\IndexDealerConfigurationSettingsRequest;
use App\Http\Requests\Backoffice\DealerConfiguration\Settings\UpdateDealerConfigurationSettingsRequest;
use App\Http\Resources\Backoffice\Settings\ConfigurationFieldResource;
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

    public function index(IndexDealerConfigurationSettingsRequest $request): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;

        $rows = $this->configurationManager
            ->syncDealerDefaults($dealer)
            ->where('backoffice_only', false)
            ->values();

        return Inertia::render('DealerConfiguration/Settings/Index', [
            'publicTitle' => 'Configuration',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'settings' => ConfigurationFieldResource::collection($rows)->resolve(),
            'categoryOptions' => $this->catalog->categoryOptions(),
            'timezoneOptions' => $this->catalog->timezoneOptions(),
            'stockTypeOptions' => StockOptions::types(withAll: false)->resolve(),
            'updateRoute' => route('backoffice.dealer-configuration.settings.update'),
            'canUpdate' => true,
        ]);
    }

    public function update(UpdateDealerConfigurationSettingsRequest $request): RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;

        $this->configurationManager->syncDealerDefaults($dealer);
        $this->configurationManager->updateDealerValues(
            dealer: $dealer,
            settings: (array) $request->validated('settings'),
            includeBackofficeOnly: false
        );

        return back()->with('success', 'Settings updated.');
    }
}
