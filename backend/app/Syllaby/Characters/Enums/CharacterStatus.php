<?php

namespace App\Syllaby\Characters\Enums;

enum CharacterStatus: string
{
    case DRAFT = 'draft';

    case PREVIEW_GENERATING = 'preview-generating';
    case PREVIEW_READY = 'preview-ready';
    case PREVIEW_FAILED = 'preview-failed';

    case POSE_GENERATING = 'pose-generating';
    case POSE_READY = 'pose-ready';
    case POSE_FAILED = 'pose-failed';

    case MODEL_TRAINING = 'model-training';
    case MODEL_TRAINING_FAILED = 'model-training-failed';

    case READY = 'ready';

    public function inProgress(): bool
    {
        return in_array($this->value, [
            self::PREVIEW_GENERATING->value,
            self::POSE_GENERATING->value,
            self::MODEL_TRAINING->value,
        ]);
    }

    public function is(self $character): bool
    {
        return $this->value === $character->value;
    }
}
