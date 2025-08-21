<?php

use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Enums\CreditEventTypeEnum;
use App\Syllaby\Credits\Enums\CreditCalculationTypeEnum;

return [
    'events' => [
        CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID->value => [
            'type' => CreditEventTypeEnum::ADDED->value,
            'calculation_type' => CreditCalculationTypeEnum::CALCULATIVE->value,
            'value' => 20,
        ],
        CreditEventEnum::SUBSCRIBE_TO_TRIAL->value => [
            'type' => CreditEventTypeEnum::ADDED->value,
            'calculation_type' => CreditCalculationTypeEnum::FIXED->value,
            'value' => 500,
        ],
        CreditEventEnum::PRORATED_CREDITS->value => [
            'type' => CreditEventTypeEnum::ADDED->value,
            'calculation_type' => CreditCalculationTypeEnum::FIXED->value,
            'value' => 0,
        ],
        CreditEventEnum::MONTHLY_CREDITS_ADDED->value => [
            'type' => CreditEventTypeEnum::ADDED->value,
            'calculation_type' => CreditCalculationTypeEnum::CALCULATIVE->value,
            'value' => 1,
        ],
        CreditEventEnum::ADMIN_CREDIT_ADDED->value => [
            'type' => CreditEventTypeEnum::ADDED->value,
            'calculation_type' => CreditCalculationTypeEnum::CALCULATIVE->value,
            'value' => 1,
        ],
        CreditEventEnum::CUSTOM_CREDIT_PURCHASED->value => [
            'type' => CreditEventTypeEnum::ADDED->value,
            'calculation_type' => CreditCalculationTypeEnum::FIXED->value,
            'value' => 0,
        ],
        CreditEventEnum::REFUNDED_CREDITS->value => [
            'type' => CreditEventTypeEnum::ADDED->value,
            'calculation_type' => CreditCalculationTypeEnum::FIXED->value,
            'value' => 0,
        ],
        CreditEventEnum::IDEA_DISCOVERED->value => [
            'type' => CreditEventTypeEnum::SPEND->value,
            'calculation_type' => CreditCalculationTypeEnum::FIXED->value,
            'value' => 15,
            'min_amount' => 15,
        ],
        CreditEventEnum::CONTENT_PROMPT_REQUESTED->value => [
            'type' => CreditEventTypeEnum::SPEND->value,
            'calculation_type' => CreditCalculationTypeEnum::CALCULATIVE->value,
            'value' => 0.01,
            'min_amount' => 1,
        ],
        CreditEventEnum::VIDEO_GENERATED->value => [
            'type' => CreditEventTypeEnum::SPEND->value,
            'calculation_type' => CreditCalculationTypeEnum::CALCULATIVE->value,
            'value' => 2,
            'min_amount' => 6,
        ],
        CreditEventEnum::REAL_CLONE_GENERATED->value => [
            'type' => CreditEventTypeEnum::SPEND->value,
            'calculation_type' => CreditCalculationTypeEnum::CALCULATIVE->value,
            'value' => 2,
            'min_amount' => 50,
        ],
        CreditEventEnum::TEXT_TO_SPEECH_GENERATED->value => [
            'type' => CreditEventTypeEnum::SPEND->value,
            'calculation_type' => CreditCalculationTypeEnum::CALCULATIVE->value,
            'value' => 2,
            'min_amount' => 7,
        ],
        CreditEventEnum::REAL_CLONE_AND_TEXT_TO_SPEECH->value => [
            'type' => CreditEventTypeEnum::SPEND->value,
            'calculation_type' => CreditCalculationTypeEnum::CALCULATIVE->value,
            'value' => 2,
            'min_amount' => 57,
        ],
        CreditEventEnum::BULK_AI_IMAGES_GENERATED->value => [
            'type' => CreditEventTypeEnum::SPEND->value,
            'calculation_type' => CreditCalculationTypeEnum::CALCULATIVE->value,
            'value' => 2,
            'min_amount' => 3,
        ],
        CreditEventEnum::SINGLE_AI_IMAGE_GENERATED->value => [
            'type' => CreditEventTypeEnum::SPEND->value,
            'calculation_type' => CreditCalculationTypeEnum::FIXED->value,
            'value' => 1,
            'min_amount' => 1,
        ],
        CreditEventEnum::FACELESS_VIDEO_GENERATED->value => [
            'type' => CreditEventTypeEnum::SPEND->value,
            'calculation_type' => CreditCalculationTypeEnum::CALCULATIVE->value,
            'value' => 2,
            'min_amount' => 13,
        ],
        CreditEventEnum::FACELESS_VIDEO_EXPORTED->value => [
            'type' => CreditEventTypeEnum::SPEND->value,
            'calculation_type' => CreditCalculationTypeEnum::CALCULATIVE->value,
            'value' => 2,
            'min_amount' => 3,
        ],
        CreditEventEnum::IMAGE_ANIMATED->value => [
            'type' => CreditEventTypeEnum::SPEND->value,
            'calculation_type' => CreditCalculationTypeEnum::FIXED->value,
            'value' => 10,
            'min_amount' => 10,
        ],
        CreditEventEnum::AUDIO_TRANSCRIPTION->value => [
            'type' => CreditEventTypeEnum::SPEND->value,
            'calculation_type' => CreditCalculationTypeEnum::CALCULATIVE->value,
            'value' => 2,
            'min_amount' => 1,
        ],
        CreditEventEnum::CUSTOM_CHARACTER_PURCHASED->value => [
            'type' => CreditEventTypeEnum::SPEND->value,
            'calculation_type' => CreditCalculationTypeEnum::FIXED->value,
            'value' => 50,
            'min_amount' => 50,
        ]
    ],

    'video' => [
        'heygen' => 50,
        'd-id' => 30,
        'fastvideo' => 30,
        'creatomate' => 6, // For 60 seconds video.
    ],

    'images' => [
        'replicate' => 3, // Assuming 10 images with 10 seconds of runtime
    ],

    'transcription' => [
        'whisper' => 1, // 1 credit per 60 seconds of audio
    ],

    'audio' => [
        'elevenlabs' => 7, // For 1000 characters
    ],
];
