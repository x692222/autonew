<?php

namespace App\Http\Controllers\Backoffice\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\System\Settings\IndexSystemSettingsRequest;
use App\Http\Requests\Backoffice\System\Settings\UpdateSystemSettingsRequest;
use App\Http\Resources\Backoffice\Settings\ConfigurationFieldResource;
use App\Support\Settings\ConfigurationCatalog;
use App\Support\Settings\ConfigurationManager;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SystemConfigurationsController extends Controller
{
    public function __construct(
        private readonly ConfigurationManager $configurationManager,
        private readonly ConfigurationCatalog $catalog
    ) {
    }

    public function index(IndexSystemSettingsRequest $request): Response
    {
        $rows = $this->configurationManager->syncSystemDefaults();

        return Inertia::render('System/Settings/Index', [
            'publicTitle' => 'System Configuration',
            'settings' => ConfigurationFieldResource::collection($rows)->resolve(),
            'categoryOptions' => $this->catalog->categoryOptions(),
            'timezoneOptions' => $this->catalog->timezoneOptions(),
            'updateRoute' => route('backoffice.system.settings.update'),
            'canUpdate' => true,
        ]);
    }

    public function update(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        $this->configurationManager->syncSystemDefaults();
        $this->configurationManager->updateSystemValues((array) $request->validated('settings'));

        return back()->with('success', 'System settings updated.');
    }
}
