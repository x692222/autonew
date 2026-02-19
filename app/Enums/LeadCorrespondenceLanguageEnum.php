<?php

namespace App\Enums;

enum LeadCorrespondenceLanguageEnum: string
{
    case AFRIKAANS = 'afrikaans';
    case ENGLISH = 'english';
    case GERMAN = 'german';
    case SPANISH = 'spanish';
    case FRENCH = 'french';
    case ITALIAN = 'italian';
    case DUTCH = 'dutch';
    case PORTUGUESE = 'portuguese';
    case POLISH = 'polish';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

