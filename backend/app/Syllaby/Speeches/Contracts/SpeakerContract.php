<?php

namespace App\Syllaby\Speeches\Contracts;

use App\Syllaby\Speeches\Speech;
use App\Syllaby\RealClones\RealClone;

interface SpeakerContract
{
    /**
     * Generate a speech for the given digital twin.
     */
    public function generate(Speech $speech, RealClone $clone): Speech;

    /**
     * Get all available provider voices.
     */
    public function voices(array $allowed): void;

    /**
     * Calculate the amount of credits to be charged.
     */
    public function credits(string $text): int;

    /**
     * Calculate and charge the user credits.
     */
    public function charge(Speech $speech, string $text): void;
}
