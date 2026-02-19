<?php

namespace App\Support\Settings;

use App\Models\System\Configuration\SystemConfiguration;

class SystemSettingsResolver
{
    public function __construct(private readonly ConfigurationCatalog $catalog)
    {
    }

    public function resolve(?array $keys = null): array
    {
        $definitions = $this->catalog->systemDefinitions();

        $selectedDefinitions = collect($definitions)
            ->when(is_array($keys) && $keys !== [], fn ($collection) => $collection->only($keys))
            ->all();

        $values = [];
        foreach ($selectedDefinitions as $key => $definition) {
            $values[$key] = $this->catalog->castValue(
                $definition['type'],
                $this->catalog->normalizeValue($definition['type'], $definition['default'] ?? null)
            );
        }

        if ($selectedDefinitions === []) {
            return $values;
        }

        $rows = SystemConfiguration::query()
            ->whereIn('key', array_keys($selectedDefinitions))
            ->select(['key', 'type', 'value'])
            ->get();

        foreach ($rows as $row) {
            $key = (string) $row->key;
            $definition = $selectedDefinitions[$key] ?? null;
            if (! is_array($definition)) {
                continue;
            }

            $values[$key] = $this->catalog->castValue($definition['type'], $row->value);
        }

        return $values;
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        $values = $this->resolve([$key]);

        return $values[$key] ?? $fallback;
    }
}

