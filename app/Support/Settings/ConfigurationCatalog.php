<?php

namespace App\Support\Settings;

use App\Models\Stock\Stock;
use App\Enums\ConfigurationCategoryEnum;
use App\Enums\ConfigurationValueTypeEnum;
use DateTimeZone;
use Illuminate\Validation\Rule;

class ConfigurationCatalog
{
    public function systemDefinitions(): array
    {
        return [
            'system_currency' => [
                'label' => 'System Currency',
                'category' => ConfigurationCategoryEnum::GENERAL,
                'type' => ConfigurationValueTypeEnum::TEXT,
                'default' => 'N$',
                'description' => 'Currency symbol prefix used before displayed monetary values across the platform.',
            ],
            'system_timezone' => [
                'label' => 'System Timezone',
                'category' => ConfigurationCategoryEnum::GENERAL,
                'type' => ConfigurationValueTypeEnum::TIMEZONE,
                'default' => config('app.timezone', 'UTC'),
                'description' => 'Default timezone used for system dates, times, and scheduled processing.',
            ],
            'default_locale' => [
                'label' => 'Default Locale',
                'category' => ConfigurationCategoryEnum::GENERAL,
                'type' => ConfigurationValueTypeEnum::TEXT,
                'default' => 'en',
                'description' => 'Default language/locale used by the platform when no user preference is set.',
            ],
            'default_country_code' => [
                'label' => 'Default Country Code',
                'category' => ConfigurationCategoryEnum::GENERAL,
                'type' => ConfigurationValueTypeEnum::TEXT,
                'default' => 'US',
                'description' => 'Default ISO country code used for country-specific defaults and formatting.',
            ],
            'system_is_vat_registered' => [
                'label' => 'System VAT Registered',
                'category' => ConfigurationCategoryEnum::GENERAL,
                'type' => ConfigurationValueTypeEnum::BOOLEAN,
                'default' => false,
                'description' => 'When enabled, VAT calculations are applied to system quotations.',
            ],
            'system_vat_percentage' => [
                'label' => 'System VAT Percentage',
                'category' => ConfigurationCategoryEnum::GENERAL,
                'type' => ConfigurationValueTypeEnum::FLOAT,
                'default' => null,
                'description' => 'VAT rate percentage used for system quotations when VAT is enabled.',
            ],
            'system_vat_number' => [
                'label' => 'System VAT Number',
                'category' => ConfigurationCategoryEnum::GENERAL,
                'type' => ConfigurationValueTypeEnum::TEXT,
                'default' => null,
                'description' => 'VAT registration number printed on system quotations when VAT is enabled.',
            ],
        ];
    }

    public function dealerDefinitions(): array
    {
        return [
            'dealer_currency' => [
                'label' => 'Dealer Currency',
                'category' => ConfigurationCategoryEnum::GENERAL,
                'type' => ConfigurationValueTypeEnum::TEXT,
                'default' => 'N$',
                'description' => 'Currency symbol prefix used before this dealer\'s displayed monetary values.',
                'backoffice_only' => false,
            ],
            'dealer_is_vat_registered' => [
                'label' => 'Dealer VAT Registered',
                'category' => ConfigurationCategoryEnum::GENERAL,
                'type' => ConfigurationValueTypeEnum::BOOLEAN,
                'default' => false,
                'description' => 'When enabled, VAT calculations are applied to dealer quotations.',
                'backoffice_only' => false,
            ],
            'dealer_vat_percentage' => [
                'label' => 'Dealer VAT Percentage',
                'category' => ConfigurationCategoryEnum::GENERAL,
                'type' => ConfigurationValueTypeEnum::FLOAT,
                'default' => null,
                'description' => 'VAT rate percentage used for dealer quotations when VAT is enabled.',
                'backoffice_only' => false,
            ],
            'dealer_vat_number' => [
                'label' => 'Dealer VAT Number',
                'category' => ConfigurationCategoryEnum::GENERAL,
                'type' => ConfigurationValueTypeEnum::TEXT,
                'default' => null,
                'description' => 'VAT registration number printed on dealer quotations when VAT is enabled.',
                'backoffice_only' => false,
            ],
            'maximum_files_in_bucket' => [
                'label' => 'Maximum Files in Bucket',
                'category' => ConfigurationCategoryEnum::STORAGE,
                'type' => ConfigurationValueTypeEnum::NUMBER,
                'default' => 0,
                'description' => 'Maximum number of unassigned files allowed in the dealer upload bucket. 0 means unlimited.',
                'backoffice_only' => true,
            ],
            'maximum_images' => [
                'label' => 'Maximum Images per Stock Item',
                'category' => ConfigurationCategoryEnum::STOCK,
                'type' => ConfigurationValueTypeEnum::NUMBER,
                'default' => 0,
                'description' => 'Maximum number of images allowed for a single stock item. 0 means unlimited.',
                'backoffice_only' => true,
            ],
            'minimum_images_required_for_live' => [
                'label' => 'Minimum Images Required for Live Status',
                'category' => ConfigurationCategoryEnum::STOCK,
                'type' => ConfigurationValueTypeEnum::NUMBER,
                'default' => (int) config('stock.live_min_images', 3),
                'description' => 'Minimum number of uploaded images required before a stock item can be live.',
                'backoffice_only' => true,
            ],
            'default_stock_type_filter' => [
                'label' => 'Default Type Filter',
                'category' => ConfigurationCategoryEnum::STOCK,
                'type' => ConfigurationValueTypeEnum::TEXT,
                'default' => null,
                'description' => 'Default stock type selected on the stock index filter for this dealer.',
                'backoffice_only' => false,
            ],
            'is_whatsapp_enabled_for_backoffice' => [
                'label' => 'Enable WhatsApp for Backoffice Workflows',
                'category' => ConfigurationCategoryEnum::WHATSAPP,
                'type' => ConfigurationValueTypeEnum::BOOLEAN,
                'default' => false,
                'description' => 'Enables internal WhatsApp-driven workflows such as stock image ingestion.',
                'backoffice_only' => true,
            ],
            'is_whatsapp_enabled_for_dealer' => [
                'label' => 'Enable WhatsApp for Dealer Customers',
                'category' => ConfigurationCategoryEnum::WHATSAPP,
                'type' => ConfigurationValueTypeEnum::BOOLEAN,
                'default' => false,
                'description' => 'Enables WhatsApp communication with customers for this dealer.',
                'backoffice_only' => true,
            ],
            'is_ai_enabled_for_dealer' => [
                'label' => 'Enable AI for Dealer',
                'category' => ConfigurationCategoryEnum::ARTIFICIAL_INTELLIGENCE,
                'type' => ConfigurationValueTypeEnum::BOOLEAN,
                'default' => false,
                'description' => 'Enables AI-powered workflows such as summaries, reporting, and lead responses.',
                'backoffice_only' => true,
            ],
            'hours_to_reassign' => [
                'label' => 'Hours to Reassign Lead',
                'category' => ConfigurationCategoryEnum::LEAD_MANAGEMENT,
                'type' => ConfigurationValueTypeEnum::NUMBER,
                'default' => 2,
                'description' => 'Number of hours before an unanswered inbound lead is returned to the unassigned pool.',
                'backoffice_only' => true,
            ],
            'rate_per_standard_whatsapp_message' => [
                'label' => 'Rate per Standard WhatsApp Message',
                'category' => ConfigurationCategoryEnum::BILLING,
                'type' => ConfigurationValueTypeEnum::FLOAT,
                'default' => 0,
                'description' => 'Billing rate per inbound or outbound standard WhatsApp message in the 24-hour window.',
                'backoffice_only' => true,
            ],
            'rate_per_template_whatsapp_message' => [
                'label' => 'Rate per Template WhatsApp Message',
                'category' => ConfigurationCategoryEnum::BILLING,
                'type' => ConfigurationValueTypeEnum::FLOAT,
                'default' => 0,
                'description' => 'Billing rate per WhatsApp template message sent.',
                'backoffice_only' => true,
            ],
            'rate_per_ai_input_million_tokens' => [
                'label' => 'Rate per AI Input Million Tokens',
                'category' => ConfigurationCategoryEnum::BILLING,
                'type' => ConfigurationValueTypeEnum::FLOAT,
                'default' => 0,
                'description' => 'Billing rate per one million AI input tokens consumed.',
                'backoffice_only' => true,
            ],
            'rate_per_ai_output_million_tokens' => [
                'label' => 'Rate per AI Output Million Tokens',
                'category' => ConfigurationCategoryEnum::BILLING,
                'type' => ConfigurationValueTypeEnum::FLOAT,
                'default' => 0,
                'description' => 'Billing rate per one million AI output tokens generated.',
                'backoffice_only' => true,
            ],
            'max_concurrent_published_stock_items' => [
                'label' => 'Max Concurrent Published Stock Items',
                'category' => ConfigurationCategoryEnum::STOCK,
                'type' => ConfigurationValueTypeEnum::NUMBER,
                'default' => 0,
                'description' => 'Maximum number of stock items that can be published at the same time. 0 means unlimited.',
                'backoffice_only' => true,
            ],
            'max_historical_published_stock_items' => [
                'label' => 'Max Historical Published Stock Items',
                'category' => ConfigurationCategoryEnum::STOCK,
                'type' => ConfigurationValueTypeEnum::NUMBER,
                'default' => 0,
                'description' => 'Maximum total number of stock items that may be published since account inception. 0 means unlimited.',
                'backoffice_only' => true,
            ],
            'lead_acknowledgement_minutes' => [
                'label' => 'Lead Acknowledgement SLA (Minutes)',
                'category' => ConfigurationCategoryEnum::LEAD_MANAGEMENT,
                'type' => ConfigurationValueTypeEnum::NUMBER,
                'default' => 15,
                'description' => 'Expected number of minutes to acknowledge a newly assigned lead.',
                'backoffice_only' => true,
            ],
            'notify_on_new_lead' => [
                'label' => 'Notify on New Lead',
                'category' => ConfigurationCategoryEnum::NOTIFICATIONS,
                'type' => ConfigurationValueTypeEnum::BOOLEAN,
                'default' => true,
                'description' => 'When enabled, dealer users receive an email notification when new leads are assigned.',
                'backoffice_only' => false,
            ],
        ];
    }

    public function timezoneOptions(): array
    {
        return collect(DateTimeZone::listIdentifiers())
            ->unique()
            ->sort()
            ->map(fn (string $timezone) => ['label' => $timezone, 'value' => $timezone])
            ->values()
            ->all();
    }

    public function systemValidationRules(): array
    {
        return $this->definitionValidationRules($this->systemDefinitions());
    }

    public function dealerValidationRules(bool $includeBackofficeOnly = true): array
    {
        $definitions = collect($this->dealerDefinitions())
            ->filter(fn (array $definition) => $includeBackofficeOnly || ($definition['backoffice_only'] ?? false) === false)
            ->all();

        return $this->definitionValidationRules($definitions);
    }

    public function normalizeValue(ConfigurationValueTypeEnum $type, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            ConfigurationValueTypeEnum::BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            ConfigurationValueTypeEnum::NUMBER => (string) (int) $value,
            ConfigurationValueTypeEnum::FLOAT => (string) (float) $value,
            default => (string) $value,
        };
    }

    public function castValue(ConfigurationValueTypeEnum|string $type, ?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($type)) {
            $type = ConfigurationValueTypeEnum::from($type);
        }

        return match ($type) {
            ConfigurationValueTypeEnum::BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            ConfigurationValueTypeEnum::NUMBER => (int) $value,
            ConfigurationValueTypeEnum::FLOAT => (float) $value,
            default => $value,
        };
    }

    public function categoryOptions(): array
    {
        return collect(ConfigurationCategoryEnum::cases())
            ->map(fn (ConfigurationCategoryEnum $category) => [
                'label' => $category->label(),
                'value' => $category->value,
            ])
            ->values()
            ->all();
    }

    public function definitionKeys(array $definitions): array
    {
        return array_keys($definitions);
    }

    private function definitionValidationRules(array $definitions): array
    {
        $rules = [
            'settings' => ['required', 'array'],
        ];

        foreach ($definitions as $key => $definition) {
            /** @var ConfigurationValueTypeEnum $type */
            $type = $definition['type'];

            if ($key === 'default_stock_type_filter') {
                $rules["settings.{$key}"] = ['nullable', Rule::in(Stock::STOCK_TYPE_OPTIONS)];
                continue;
            }

            if ($key === 'minimum_images_required_for_live') {
                $rules["settings.{$key}"] = ['nullable', 'integer', 'min:1', 'max:50'];
                continue;
            }

            if (in_array($key, ['system_vat_percentage', 'dealer_vat_percentage'], true)) {
                $rules["settings.{$key}"] = ['nullable', 'numeric', 'gt:0', 'lt:100'];
                continue;
            }

            if (in_array($key, ['system_vat_number', 'dealer_vat_number'], true)) {
                $rules["settings.{$key}"] = ['nullable', 'string', 'max:255'];
                continue;
            }

            $rules["settings.{$key}"] = match ($type) {
                ConfigurationValueTypeEnum::BOOLEAN => ['nullable', 'boolean'],
                ConfigurationValueTypeEnum::NUMBER => ['nullable', 'integer', 'min:0', 'max:100000000'],
                ConfigurationValueTypeEnum::FLOAT => ['nullable', 'numeric', 'min:0', 'max:100000000'],
                ConfigurationValueTypeEnum::TIMEZONE => ['nullable', Rule::in(DateTimeZone::listIdentifiers())],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        return $rules;
    }
}
