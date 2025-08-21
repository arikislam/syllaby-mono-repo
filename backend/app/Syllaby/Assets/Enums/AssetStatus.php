<?php

namespace App\Syllaby\Assets\Enums;

use App\System\Traits\HasEnumValues;

enum AssetStatus: string
{
    use HasEnumValues;

    case SUCCESS = 'success';
    case FAILED = 'failed';
    case PROCESSING = 'processing';

    public function is(AssetStatus $status): bool
    {
        return $this->value === $status->value;
    }
}
