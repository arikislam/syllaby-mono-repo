<?php

namespace App\Syllaby\Generators\Contracts;

use App\Syllaby\Generators\DTOs\CaptionResponse;

interface TranscriberContract
{
    /**
     * Run the transcription process.
     */
    public function run(string $url, array $options = []): ?CaptionResponse;

    /**
     * Calculate the number of credits for the given duration.
     */
    public function credits(float $duration): int;
}
