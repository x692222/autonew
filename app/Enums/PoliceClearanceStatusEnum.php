<?php

namespace App\Enums;

enum PoliceClearanceStatusEnum: string
{
    case YES = 'yes';
    case NO = 'no';
    case UNDEFINED = 'undefined';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

