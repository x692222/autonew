<?php

use App\Services\ApiProviders\Whatsapp\Drivers\LogDriver;
use App\Services\ApiProviders\Whatsapp\Drivers\TwilioDriver;

return [
    'providers' => [
        'twilio' => TwilioDriver::class,
        'log' => LogDriver::class,
    ],
];
