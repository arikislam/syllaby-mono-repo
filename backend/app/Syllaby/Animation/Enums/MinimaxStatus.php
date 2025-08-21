<?php

namespace App\Syllaby\Animation\Enums;

enum MinimaxStatus: int
{
    case SUCCESS = 0;
    case RATE_LIMIT = 1002;
    case AUTH_FAILED = 1004;
    case INSUFFICIENT_BALANCE = 1008;
    case ABNORMAL_PARAMETERS = 1013;
    case SENSITIVE_PROMPT = 1026;
    case SENSITIVE_VIDEO = 1027;

    case UNKNOWN_ERRORS = 1000;
    case UNKNOWN = 1003;
    case UNKNOWN_STATUS = 1033;
    case TIMEOUT = 1001;
    case TOKEN_RATE_LIMIT = 1039;
    case INVALID_PARAMETERS = 2013;

    public function getPublicMessage(): string
    {
        return match ($this) {
            self::SUCCESS => 'Success',
            self::SENSITIVE_PROMPT => 'The prompt contains sensitive information',
            self::SENSITIVE_VIDEO => 'The generated video contains sensitive content',
            self::INVALID_PARAMETERS, self::ABNORMAL_PARAMETERS => 'One or more parameters are invalid',
            self::TIMEOUT => 'Timeout error',
            default => 'Something went wrong. Please try again later',
        };
    }

    public function isFailed(): bool
    {
        return $this->value !== self::SUCCESS->value;
    }
}
