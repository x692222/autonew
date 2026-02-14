<?php

namespace App\Support\Options;

use Illuminate\Support\Collection;

class AbstractOptions
{

    final public static function prependAll(Collection $options, string $label = 'All', string $value = ''): Collection
    {
        return collect(
            [
                [
                    'label' => $label,
                    'value' => $value,
                ]
            ]
        )->concat($options)->values();
    }

}
