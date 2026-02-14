<?php

namespace App\Support\Options;

use App\Http\Resources\KeyValueOptions\GeneralCollection;
use App\Models\Leads\Lead;
use Illuminate\Support\Str;

final class LeadOptions extends AbstractOptions
{

    public static function sourceOptions(bool $withAll = false): GeneralCollection
    {
        $items = cache()->rememberForever("lead:source_options:{$withAll}:v1", function () {
            return collect(Lead::LEAD_SOURCES)
                ->map(fn (string $source) => [
                    'label' => Str::headline($source),
                    'value' => $source,
                ])
                ->values()
                ->all();
        });

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new GeneralCollection($options);
    }

    public static function channelOptions(bool $withAll = false): GeneralCollection
    {
        $items = cache()->rememberForever("lead:channel_options:{$withAll}:v1", function () {
            return collect(Lead::LEAD_CHANNELS)
                ->map(fn (string $source) => [
                    'label' => Str::headline($source),
                    'value' => $source,
                ])
                ->values()
                ->all();
        });

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new GeneralCollection($options);
    }

    public static function statusOptions(bool $withAll = false): GeneralCollection
    {
        $items = cache()->rememberForever("lead:status_options:{$withAll}:v1", function () {
            return collect(Lead::LEAD_STATUSES)
                ->map(fn (string $source) => [
                    'label' => Str::headline($source),
                    'value' => $source,
                ])
                ->values()
                ->all();
        });

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new GeneralCollection($options);
    }

}
