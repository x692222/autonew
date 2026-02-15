<?php

namespace App\Enums;

enum ConfigurationValueTypeEnum: string
{
    case TEXT = 'text';
    case NUMBER = 'number';
    case FLOAT = 'float';
    case BOOLEAN = 'boolean';
    case TIMEZONE = 'timezone';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
