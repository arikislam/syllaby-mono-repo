<?php

namespace App\Syllaby\Analytics\Enum;

enum AnalyticsType: string
{
    case TRIAL_STARTED = 'trial_started';

    case SUBSCRIPTION_STARTED = 'subscription_started';
}
