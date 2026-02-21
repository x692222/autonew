<?php

namespace App\Support\Settings;

use App\Models\Stock\Stock;
use App\Enums\ConfigurationCategoryEnum;
use App\Enums\ConfigurationValueTypeEnum;
use App\Support\Options\GeneralOptions;
use DateTimeZone;
use Illuminate\Validation\Rule;

class ConfigurationCatalog
{
    public function systemDefinitions(): array
    {
        return [
            'system_currency' => [
                'label' => 'System Currency',
                'category' => ConfigurationCategoryEnum::BILLING,
                'type' => ConfigurationValueTypeEnum::TEXT,
                'default' => 'N$',
                'description' => 'Currency symbol prefix used before displayed monetary values across the platform.',
            ],
            'contact_no_prefix' => [
                'label' => 'Contact Number Prefix',
                'category' => ConfigurationCategoryEnum::GENERAL,
                'type' => ConfigurationValueTypeEnum::TEXT,
                'default' => null,
                'description' => 'Default international dialing prefix used to prefill contact number inputs (example: +264).',
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
                'category' => ConfigurationCategoryEnum::BILLING,
                'type' => ConfigurationValueTypeEnum::BOOLEAN,
                'default' => false,
                'description' => 'When enabled, VAT calculations are applied to system quotations.',
            ],
            'system_vat_percentage' => [
                'label' => 'System VAT Percentage',
                'category' => ConfigurationCategoryEnum::BILLING,
                'type' => ConfigurationValueTypeEnum::FLOAT,
                'default' => null,
                'description' => 'VAT rate percentage used for system quotations when VAT is enabled.',
            ],
            'system_vat_number' => [
                'label' => 'System VAT Number',
                'category' => ConfigurationCategoryEnum::BILLING,
                'type' => ConfigurationValueTypeEnum::TEXT,
                'default' => null,
                'description' => 'VAT registration number printed on system quotations when VAT is enabled.',
            ],
            'can_edit_invoice_after_partial_payment' => [
                'label' => 'Allow Invoice Edit After Partial Payment',
                'category' => ConfigurationCategoryEnum::BILLING,
                'type' => ConfigurationValueTypeEnum::BOOLEAN,
                'default' => false,
                'description' => 'When enabled, system invoices become read-only after partial payment has been captured.',
            ],
            'can_edit_invoice_after_full_payment' => [
                'label' => 'Allow Invoice Edit After Full Payment',
                'category' => ConfigurationCategoryEnum::BILLING,
                'type' => ConfigurationValueTypeEnum::BOOLEAN,
                'default' => false,
                'description' => 'When enabled, fully paid system invoices become read-only.',
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
            'contact_no_prefix' => [
                'label' => 'Contact Number Prefix',
                'category' => ConfigurationCategoryEnum::GENERAL,
                'type' => ConfigurationValueTypeEnum::TEXT,
                'default' => null,
                'description' => 'Default international dialing prefix used to prefill customer contact number inputs (example: +264).',
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
            'can_edit_invoice_after_partial_payment' => [
                'label' => 'Allow Invoice Edit After Partial Payment',
                'category' => ConfigurationCategoryEnum::BILLING,
                'type' => ConfigurationValueTypeEnum::BOOLEAN,
                'default' => false,
                'description' => 'When enabled, dealer invoices become read-only after partial payment has been captured.',
                'backoffice_only' => false,
            ],
            'can_edit_invoice_after_full_payment' => [
                'label' => 'Allow Invoice Edit After Full Payment',
                'category' => ConfigurationCategoryEnum::BILLING,
                'type' => ConfigurationValueTypeEnum::BOOLEAN,
                'default' => false,
                'description' => 'When enabled, fully paid dealer invoices become read-only.',
                'backoffice_only' => false,
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
        return GeneralOptions::timezoneOptions()->resolve();
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

        $rules = $this->definitionValidationRules($definitions);

        $rules['settings.dealer_currency'] = ['required', 'string', 'max:255'];
        $rules['settings.contact_no_prefix'] = ['required', 'string', 'max:10', 'regex:/^\\+[0-9]+$/'];

        $requiredNumericKeys = [
            'rate_per_ai_input_million_tokens',
            'rate_per_ai_output_million_tokens',
            'rate_per_standard_whatsapp_message',
            'rate_per_template_whatsapp_message',
            'hours_to_reassign',
            'lead_acknowledgement_minutes',
            'max_historical_published_stock_items',
            'minimum_images_required_for_live',
            'max_concurrent_published_stock_items',
            'maximum_images',
            'maximum_files_in_bucket',
        ];

        foreach ($requiredNumericKeys as $key) {
            if (! isset($definitions[$key])) {
                continue;
            }

            $type = $definitions[$key]['type'];
            $rules["settings.{$key}"] = match ($type) {
                ConfigurationValueTypeEnum::FLOAT => ['required', 'numeric', 'min:0', 'max:100000000'],
                default => ['required', 'integer', 'min:0', 'max:100000000'],
            };
        }

        return $rules;
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
        return GeneralOptions::configurationCategories()->resolve();
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
                $rules["settings.{$key}"] = ['nullable', 'integer', 'min:0', 'max:50'];
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

            if ($key === 'contact_no_prefix') {
                $rules["settings.{$key}"] = ['nullable', 'string', 'max:10', 'regex:/^\\+[0-9]+$/'];
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
