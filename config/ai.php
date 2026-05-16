<?php

return [
    'default' => env('AI_DEFAULT_PROVIDER', 'openai'),

    'fallback_enabled' => env('AI_FALLBACK_ENABLED', true),

    'fallback_chain' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('AI_FALLBACK_CHAIN', 'openai,gemini,ollama'))
    ))),

    'timeout_seconds' => env('AI_TIMEOUT_SECONDS', 5),

    'ollama_timeout_seconds' => env('OLLAMA_TIMEOUT_SECONDS', 60),

    'rate_limits' => [
        'per_minute' => env('AI_RATE_LIMIT_PER_MINUTE', 20),
        'per_day' => env('AI_RATE_LIMIT_PER_DAY', 500),
    ],

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        ],
        'huggingface' => [
            'api_key' => env('HF_API_KEY'),
            'base_url' => env('HF_BASE_URL', 'https://api-inference.huggingface.co/models'),
            'model' => env('HF_MODEL', 'mistralai/Mistral-7B-Instruct-v0.3'),
        ],
        'ollama' => [
            'enabled' => env('OLLAMA_ENABLED', true),
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
            'model' => env('OLLAMA_MODEL', 'llama3.2'),
            'timeout_seconds' => env('OLLAMA_TIMEOUT_SECONDS', 60),
        ],
    ],
];
