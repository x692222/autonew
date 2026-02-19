<?php

namespace App\Support\Quotations;

use App\Enums\QuotationLineItemSectionEnum;

class QuotationSectionOptions
{
    public static function dealer(): array
    {
        return [
            [
                'value' => QuotationLineItemSectionEnum::GENERAL->value,
                'label' => 'General',
                'adds' => true,
                'default_vat_exempt' => false,
                'allow_stock_lookup' => true,
            ],
            [
                'value' => QuotationLineItemSectionEnum::ACCESSORIES->value,
                'label' => 'Accessories',
                'adds' => true,
                'default_vat_exempt' => false,
                'allow_stock_lookup' => false,
            ],
            [
                'value' => QuotationLineItemSectionEnum::ADMIN->value,
                'label' => 'Admin Fees',
                'adds' => true,
                'default_vat_exempt' => false,
                'allow_stock_lookup' => false,
            ],
            [
                'value' => QuotationLineItemSectionEnum::DELIVERY->value,
                'label' => 'Delivery',
                'adds' => true,
                'default_vat_exempt' => false,
                'allow_stock_lookup' => false,
            ],
            [
                'value' => QuotationLineItemSectionEnum::FINANCE_AND_INSURANCE->value,
                'label' => 'Finance & Insurance',
                'adds' => true,
                'default_vat_exempt' => false,
                'allow_stock_lookup' => false,
            ],
            [
                'value' => QuotationLineItemSectionEnum::TRADE_IN->value,
                'label' => 'Trade In',
                'adds' => false,
                'default_vat_exempt' => true,
                'allow_stock_lookup' => false,
            ],
            [
                'value' => QuotationLineItemSectionEnum::DEDUCTIONS->value,
                'label' => 'Deductions',
                'adds' => false,
                'default_vat_exempt' => true,
                'allow_stock_lookup' => false,
            ],
            [
                'value' => QuotationLineItemSectionEnum::DISCOUNTS->value,
                'label' => 'Discounts',
                'adds' => false,
                'default_vat_exempt' => true,
                'allow_stock_lookup' => false,
            ],
        ];
    }

    public static function system(): array
    {
        return [
            [
                'value' => QuotationLineItemSectionEnum::GENERAL->value,
                'label' => 'General',
                'adds' => true,
                'default_vat_exempt' => false,
                'allow_stock_lookup' => true,
            ],
            [
                'value' => QuotationLineItemSectionEnum::ADMIN->value,
                'label' => 'Admin Fees',
                'adds' => true,
                'default_vat_exempt' => false,
                'allow_stock_lookup' => false,
            ],
            [
                'value' => QuotationLineItemSectionEnum::DISCOUNTS->value,
                'label' => 'Discounts',
                'adds' => false,
                'default_vat_exempt' => true,
                'allow_stock_lookup' => false,
            ],
        ];
    }

    public static function all(): array
    {
        return self::dealer();
    }

    public static function valuesForDealer(): array
    {
        return array_column(self::dealer(), 'value');
    }

    public static function valuesForSystem(): array
    {
        return array_column(self::system(), 'value');
    }

    public static function subtractiveValues(): array
    {
        return collect(self::dealer())
            ->where('adds', false)
            ->pluck('value')
            ->values()
            ->all();
    }
}
