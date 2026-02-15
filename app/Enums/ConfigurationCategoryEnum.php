<?php

namespace App\Enums;

enum ConfigurationCategoryEnum: string
{
    case GENERAL = 'general';
    case STORAGE = 'storage';
    case STOCK = 'stock';
    case WHATSAPP = 'whatsapp';
    case ARTIFICIAL_INTELLIGENCE = 'artificial_intelligence';
    case LEAD_MANAGEMENT = 'lead_management';
    case BILLING = 'billing';
    case NOTIFICATIONS = 'notifications';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'General',
            self::STORAGE => 'Storage',
            self::STOCK => 'Stock',
            self::WHATSAPP => 'WhatsApp',
            self::ARTIFICIAL_INTELLIGENCE => 'Artificial Intelligence',
            self::LEAD_MANAGEMENT => 'Lead Management',
            self::BILLING => 'Billing',
            self::NOTIFICATIONS => 'Notifications',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
