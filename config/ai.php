<?php

return [
    'providers' => [
        'openai' => \App\Services\ApiProviders\Ai\Drivers\OpenAiChatCompletionsDriver::class,
    ],

    'default_provider' => 'openai',
    'default_model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
];
