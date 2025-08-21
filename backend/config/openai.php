<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API URL
    |--------------------------------------------------------------------------
    |
    | This is the base URL for the OpenAI API.
    |
    */
    'base_url' => env('AI_API_URL', 'https://api.openai.com/v1/'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    |
    | The API key of OpenAI to use for requests.
    |
    */
    'token' => env('AI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Model
    |--------------------------------------------------------------------------
    |
    | Visit https://platform.openai.com/docs/models for more information.
    |
    */
    'model' => env('AI_MODEL', 'gpt-4o-2024-08-06'),

    /*
    |--------------------------------------------------------------------------
    | Max Token Length
    |--------------------------------------------------------------------------
    |It shows how many tokens the API should return. Token represents a word.
    |
    */
    'max_token' => env('AI_MAX_TOKEN', 16384),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Model Temperature
    |--------------------------------------------------------------------------
    | It is used to control the randomness of the model.
    |
    */
    'temperature' => env('AI_TEMPERATURE', 1),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Model Top P
    |--------------------------------------------------------------------------
    | It controls the diversity of the generated text.
    |
    */
    'top_p' => env('AI_TOP_P', 1),

    'rate_limit' => env('OPENAI_RATE_LIMIT', 60),

    'moderation' => ['rate_limit' => 500],

    'json_schemas' => [
        'default' => [
            'type' => 'json_object',
        ],

        'genre-images' => [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'genre_prompts',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'output' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'image' => ['type' => 'string'],
                                    'excerpt' => ['type' => 'string'],
                                ],
                                'required' => ['image', 'excerpt'],
                                'additionalProperties' => false,
                            ],
                        ],
                    ],
                    'required' => ['output'],
                    'additionalProperties' => false,
                ],
            ],
        ],

        'related-topics' => [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'related_topics',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'topics' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                    'required' => ['topics'],
                    'additionalProperties' => false,
                ],
            ],
        ],

        'thumbnails' => [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'thumbnail_prompts',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'prompts' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                    'required' => ['prompts'],
                    'additionalProperties' => false,
                ],
            ],
        ],

        'expand-topic' => [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'expand_topic',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'titles' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                    'required' => ['titles'],
                    'additionalProperties' => false,
                ],
            ],
        ],

        'keyword-suggestions' => [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'keyword_suggestions',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'output' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'question' => ['type' => 'string'],
                                    'search_volume' => ['type' => 'integer'],
                                    'trend' => ['type' => 'number'],
                                    'trend_line' => ['type' => 'string'],
                                    'cpc' => ['type' => 'number'],
                                    'competition' => ['type' => 'number'],
                                ],
                                'required' => ['question', 'search_volume', 'trend', 'trend_line', 'cpc', 'competition'],
                                'additionalProperties' => false,
                            ],
                        ],
                    ],
                    'required' => ['output'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ],
        ],
    ],
];
