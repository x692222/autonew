<?php

namespace App\Support\Locations;

use App\Models\Location\LocationCity;
use App\Models\Location\LocationCountry;
use App\Models\Location\LocationState;
use App\Models\Location\LocationSuburb;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class LocationTypeResolver
{
    public const COUNTRY = 'country';
    public const STATE = 'state';
    public const CITY = 'city';
    public const SUBURB = 'suburb';

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return [self::COUNTRY, self::STATE, self::CITY, self::SUBURB];
    }

    public static function modelClass(string $type): string
    {
        return match ($type) {
            self::COUNTRY => LocationCountry::class,
            self::STATE => LocationState::class,
            self::CITY => LocationCity::class,
            self::SUBURB => LocationSuburb::class,
            default => throw new InvalidArgumentException('Invalid location type.'),
        };
    }

    public static function singularLabel(string $type): string
    {
        return match ($type) {
            self::COUNTRY => 'Country',
            self::STATE => 'State',
            self::CITY => 'City',
            self::SUBURB => 'Suburb',
            default => throw new InvalidArgumentException('Invalid location type.'),
        };
    }

    public static function findOrFail(string $type, string $id): Model
    {
        $class = self::modelClass($type);

        return $class::query()->findOrFail($id);
    }
}
