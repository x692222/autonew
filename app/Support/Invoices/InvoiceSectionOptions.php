<?php

namespace App\Support\Invoices;

use App\Enums\InvoiceLineItemSectionEnum;

class InvoiceSectionOptions
{
    public static function dealer(): array
    {
        return [
            [
                'value' => InvoiceLineItemSectionEnum::GENERAL->value,
                'label' => 'General',
                'adds' => true,
                'default_vat_exempt' => false,
                'allow_stock_lookup' => true,
            ],
            [
                'value' => InvoiceLineItemSectionEnum::ACCESSORIES->value,
                'label' => 'Accessories',
                'adds' => true,
                'default_vat_exempt' => false,
                'allow_stock_lookup' => false,
            ],
            [
                'value' => InvoiceLineItemSectionEnum::ADMIN->value,
                'label' => 'Admin Fees',
                'adds' => true,
                'default_vat_exempt' => false,
                'allow_stock_lookup' => false,
            ],
            [
                'value' => InvoiceLineItemSectionEnum::DELIVERY->value,
                'label' => 'Delivery',
                'adds' => true,
                'default_vat_exempt' => false,
                'allow_stock_lookup' => false,
            ],
            [
                'value' => InvoiceLineItemSectionEnum::FINANCE_AND_INSURANCE->value,
                'label' => 'Finance & Insurance',
                'adds' => true,
                'default_vat_exempt' => false,
                'allow_stock_lookup' => false,
            ],
            [
                'value' => InvoiceLineItemSectionEnum::TRADE_IN->value,
                'label' => 'Trade In',
                'adds' => false,
                'default_vat_exempt' => true,
                'allow_stock_lookup' => false,
            ],
            [
                'value' => InvoiceLineItemSectionEnum::DEDUCTIONS->value,
                'label' => 'Deductions',
                'adds' => false,
                'default_vat_exempt' => true,
                'allow_stock_lookup' => false,
            ],
            [
                'value' => InvoiceLineItemSectionEnum::DISCOUNTS->value,
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
                'value' => InvoiceLineItemSectionEnum::GENERAL->value,
                'label' => 'General',
                'adds' => true,
                'default_vat_exempt' => false,
                'allow_stock_lookup' => true,
            ],
            [
                'value' => InvoiceLineItemSectionEnum::ADMIN->value,
                'label' => 'Admin Fees',
                'adds' => true,
                'default_vat_exempt' => false,
                'allow_stock_lookup' => false,
            ],
            [
                'value' => InvoiceLineItemSectionEnum::DISCOUNTS->value,
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
