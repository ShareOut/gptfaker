<?php

declare(strict_types=1);

return [
    'openai_api_key' => env('FAKERGPT_OPENAI_API_KEY'),

    'model' => env('FAKERGPT_MODEL', 'text-davinci-003'),

    'max_tokens' => env('FAKERGPT_MAX_TOKENS', 256),

    'temperature' => env('FAKERGPT_TEMPERATURE', 0.7),

    'environments' => [
        'local',
    ]
];
