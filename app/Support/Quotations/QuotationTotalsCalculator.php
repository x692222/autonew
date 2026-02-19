<?php

namespace App\Support\Quotations;

use App\Support\Quotations\QuotationSectionOptions;

class QuotationTotalsCalculator
{
    public function calculate(array $lineItems, bool $vatEnabled, ?float $vatPercentage): array
    {
        $subtractive = array_flip(QuotationSectionOptions::subtractiveValues());
        $effectiveVatPercentage = $vatEnabled ? (float) ($vatPercentage ?? 0) : 0.0;
        $vatDivisor = $effectiveVatPercentage > 0 ? (100 + $effectiveVatPercentage) : 0.0;

        $total = 0.0;
        $vatAmount = 0.0;

        foreach ($lineItems as $lineItem) {
            $section = (string) ($lineItem['section'] ?? '');
            $totalValue = (float) ($lineItem['total'] ?? 0);
            $isVatExempt = (bool) ($lineItem['is_vat_exempt'] ?? false);
            $sign = isset($subtractive[$section]) ? -1 : 1;
            $signedTotal = $sign * $totalValue;
            $signedVatPortion = ($vatEnabled && $vatDivisor > 0)
                ? $signedTotal * $effectiveVatPercentage / $vatDivisor
                : 0.0;
            $signedPayable = $isVatExempt
                ? ($signedTotal - $signedVatPortion)
                : $signedTotal;

            $total += $signedPayable;

            if ($vatEnabled && !$isVatExempt && $vatDivisor > 0) {
                $vatAmount += $signedVatPortion;
            }
        }

        $total = round($total, 2);
        $vatAmount = round($vatAmount, 2);
        $subtotalBeforeVat = round($total - $vatAmount, 2);

        return [
            'subtotal_before_vat' => $subtotalBeforeVat,
            'vat_amount' => $vatAmount,
            'total_amount' => $total,
        ];
    }
}
