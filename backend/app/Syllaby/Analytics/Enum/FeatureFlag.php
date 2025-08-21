<?php

namespace App\Syllaby\Analytics\Enum;

enum FeatureFlag: string
{
    case TRIAL_CREDITS_EXPERIMENT = 'trial-credits-experiment';

    case TRIAL_CONVERSION = 'trial-conversion';
}
