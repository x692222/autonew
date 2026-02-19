<?php

use App\Enums\QuotationLineItemSectionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $values = array_map(
            fn (string $value) => "'" . str_replace("'", "''", $value) . "'",
            QuotationLineItemSectionEnum::values()
        );

        DB::statement(sprintf(
            'ALTER TABLE `quotation_line_items` MODIFY `section` ENUM(%s) NOT NULL',
            implode(', ', $values)
        ));
    }

    public function down(): void
    {
        DB::table('quotation_line_items')
            ->where('section', QuotationLineItemSectionEnum::TOTALS->value)
            ->update(['section' => QuotationLineItemSectionEnum::GENERAL->value]);

        $values = array_filter(
            QuotationLineItemSectionEnum::values(),
            fn (string $value) => $value !== QuotationLineItemSectionEnum::TOTALS->value
        );

        $quotedValues = array_map(
            fn (string $value) => "'" . str_replace("'", "''", $value) . "'",
            $values
        );

        DB::statement(sprintf(
            'ALTER TABLE `quotation_line_items` MODIFY `section` ENUM(%s) NOT NULL',
            implode(', ', $quotedValues)
        ));
    }
};
