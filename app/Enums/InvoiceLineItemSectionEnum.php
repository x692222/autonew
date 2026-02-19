<?php

namespace App\Enums;

enum InvoiceLineItemSectionEnum: string
{
    case GENERAL = 'general';
    case TRADE_IN = 'trade_in';
    case DEDUCTIONS = 'deductions';
    case ACCESSORIES = 'accessories';
    case ADMIN = 'admin';
    case TOTALS = 'totals';
    case DISCOUNTS = 'discounts';
    case DELIVERY = 'delivery';
    case FINANCE_AND_INSURANCE = 'finance_and_insurance';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
