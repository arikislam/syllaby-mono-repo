<?php

namespace App\Syllaby\Subscriptions\Services;

class GooglePlayIdGenerator
{
    public function generate(string $type, string $name): string
    {
        $env = app()->environment();
        $prefix = match ($env) {
            'production' => 'prod_sbl_',
            'staging' => 'stg_sbl',
            'testing' => 'test_sbl',
            default => 'dev_sbl',
        };

        $timestamp = time();
        $cleanName = preg_replace('/[^a-z0-9]/', '', strtolower($name));
        // Limit to 20 characters in non-production

        return $env === 'production' ?
            substr($prefix.$type.'_'.$cleanName, 0, 40) :
            substr($prefix.$type.'_'.$cleanName.'_'.$timestamp, 0, 30);

    }
}
