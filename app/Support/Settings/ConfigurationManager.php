<?php

namespace App\Support\Settings;

use App\Models\Dealer\Configuration\DealerConfiguration;
use App\Models\Dealer\Dealer;
use App\Models\System\Configuration\SystemConfiguration;
use Illuminate\Database\Eloquent\Collection;

class ConfigurationManager
{
    public function __construct(private readonly ConfigurationCatalog $catalog)
    {
    }

    public function syncSystemDefaults(): Collection
    {
        $definitions = $this->catalog->systemDefinitions();

        foreach ($definitions as $key => $definition) {
            SystemConfiguration::query()->updateOrCreate(
                ['key' => $key],
                [
                    'label' => $definition['label'],
                    'category' => $definition['category']->value,
                    'type' => $definition['type']->value,
                    'description' => $definition['description'] ?? null,
                    'value' => SystemConfiguration::query()->where('key', $key)->value('value')
                        ?? $this->catalog->normalizeValue($definition['type'], $definition['default'] ?? null),
                ]
            );
        }

        return SystemConfiguration::query()->orderBy('category')->orderBy('label')->get();
    }

    public function syncDealerDefaults(Dealer $dealer): Collection
    {
        $definitions = $this->catalog->dealerDefinitions();

        foreach ($definitions as $key => $definition) {
            DealerConfiguration::query()->updateOrCreate(
                [
                    'dealer_id' => $dealer->id,
                    'key' => $key,
                ],
                [
                    'label' => $definition['label'],
                    'category' => $definition['category']->value,
                    'type' => $definition['type']->value,
                    'description' => $definition['description'] ?? null,
                    'backoffice_only' => (bool) ($definition['backoffice_only'] ?? false),
                    'value' => DealerConfiguration::query()
                        ->where('dealer_id', $dealer->id)
                        ->where('key', $key)
                        ->value('value') ?? $this->catalog->normalizeValue($definition['type'], $definition['default'] ?? null),
                ]
            );
        }

        return DealerConfiguration::query()
            ->where('dealer_id', $dealer->id)
            ->orderBy('category')
            ->orderBy('label')
            ->get();
    }

    public function updateSystemValues(array $settings): void
    {
        $definitions = $this->catalog->systemDefinitions();

        foreach ($definitions as $key => $definition) {
            if (! array_key_exists($key, $settings)) {
                continue;
            }

            SystemConfiguration::query()
                ->where('key', $key)
                ->update([
                    'value' => $this->catalog->normalizeValue($definition['type'], $settings[$key]),
                ]);
        }
    }

    public function updateDealerValues(Dealer $dealer, array $settings, bool $includeBackofficeOnly): void
    {
        $definitions = collect($this->catalog->dealerDefinitions())
            ->filter(fn (array $definition) => $includeBackofficeOnly || ($definition['backoffice_only'] ?? false) === false)
            ->all();

        foreach ($definitions as $key => $definition) {
            if (! array_key_exists($key, $settings)) {
                continue;
            }

            DealerConfiguration::query()
                ->where('dealer_id', $dealer->id)
                ->where('key', $key)
                ->update([
                    'value' => $this->catalog->normalizeValue($definition['type'], $settings[$key]),
                ]);
        }
    }

    public function dealerDefaultRows(bool $includeBackofficeOnly = true): array
    {
        return collect($this->catalog->dealerDefinitions())
            ->filter(fn (array $definition) => $includeBackofficeOnly || ($definition['backoffice_only'] ?? false) === false)
            ->map(function (array $definition, string $key) {
                $normalized = $this->catalog->normalizeValue($definition['type'], $definition['default'] ?? null);

                return [
                    'id' => null,
                    'key' => $key,
                    'label' => (string) $definition['label'],
                    'category' => $definition['category']->value,
                    'type' => $definition['type']->value,
                    'description' => $definition['description'] ?? null,
                    'value' => $this->catalog->castValue($definition['type'], $normalized),
                    'backoffice_only' => (bool) ($definition['backoffice_only'] ?? false),
                ];
            })
            ->sortBy([
                ['category', 'asc'],
                ['label', 'asc'],
            ])
            ->values()
            ->all();
    }
}
