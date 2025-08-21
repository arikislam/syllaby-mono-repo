<?php

namespace App\Syllaby\RealClones\Contracts;

use App\Syllaby\Speeches\Speech;
use App\Syllaby\RealClones\RealClone;

interface PresenterContract
{
    /**
     * Generates a Real Clone video.
     */
    public function generate(RealClone $clone, Speech $speech): RealClone;

    /**
     * Calculate and charge the user credits.
     */
    public function charge(RealClone $clone): void;

    /**
     * Fetch and saves in storage the allowed avatars.
     */
    public function avatars(array $allowed): void;
}
