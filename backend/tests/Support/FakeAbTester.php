<?php

namespace Tests\Support;

use App\Syllaby\Analytics\Contracts\AbTester;

class FakeAbTester implements AbTester
{
    public function getFeatureFlag(string $flag, int|string $identifier): string
    {
        return 'control';
    }

    public function capture(array $data): void
    {
    }
}
