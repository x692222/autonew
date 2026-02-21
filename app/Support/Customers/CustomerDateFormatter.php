<?php

namespace App\Support\Customers;

use Illuminate\Support\Carbon;

class CustomerDateFormatter
{
    public function format(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse((string) $value)->format('Y-m-d');
    }
}
