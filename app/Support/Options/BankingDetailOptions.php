<?php

namespace App\Support\Options;

use App\Http\Resources\KeyValueOptions\GeneralCollection;
use App\Models\Billing\BankingDetail;

final class BankingDetailOptions extends AbstractOptions
{
    public static function system(bool $withAll = false): GeneralCollection
    {
        $items = BankingDetail::query()
            ->system()
            ->select(['id as value', 'bank', 'account_number'])
            ->orderBy('bank')
            ->get()
            ->map(fn ($row) => [
                'value' => $row->value,
                'label' => trim((string) ($row->bank ?? '')) . ' (' . trim((string) ($row->account_number ?? '')) . ')',
            ])
            ->values()
            ->all();

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new GeneralCollection($options);
    }

    public static function forDealer(string $dealerId, bool $withAll = false): GeneralCollection
    {
        $items = BankingDetail::query()
            ->forDealer($dealerId)
            ->select(['id as value', 'bank', 'account_number'])
            ->orderBy('bank')
            ->get()
            ->map(fn ($row) => [
                'value' => $row->value,
                'label' => trim((string) ($row->bank ?? '')) . ' (' . trim((string) ($row->account_number ?? '')) . ')',
            ])
            ->values()
            ->all();

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new GeneralCollection($options);
    }
}
