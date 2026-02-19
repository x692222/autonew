<?php

namespace App\Enums;

enum QuotationCustomerTypeEnum: string
{
    case INDIVIDUAL = 'individual';
    case COMPANY = 'company';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

