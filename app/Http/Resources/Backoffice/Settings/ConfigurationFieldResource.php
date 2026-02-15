<?php

namespace App\Http\Resources\Backoffice\Settings;

use App\Support\Settings\ConfigurationCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigurationFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $catalog = app(ConfigurationCatalog::class);

        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->label,
            'category' => $this->category?->value ?? (string) $this->category,
            'type' => $this->type?->value ?? (string) $this->type,
            'description' => $this->description,
            'value' => $catalog->castValue($this->type, $this->value),
            'backoffice_only' => (bool) ($this->backoffice_only ?? false),
        ];
    }
}
