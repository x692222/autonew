<?php

namespace App\Enums;

enum SystemRequestStatusEnum: string
{
    case SUBMITTED = 'SUBMITTED';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case PROCESSING = 'PROCESSING';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

