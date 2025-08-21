<?php

namespace App\Syllaby\Generators\Vendors\Transcribers;

use Illuminate\Support\Manager;
use App\Syllaby\Generators\Contracts\TranscriberContract;

class TranscriberManager extends Manager
{
    /**
     * Instantiates a Whisper driver.
     */
    public function createWhisperDriver(): TranscriberContract
    {
        return new Whisper;
    }

    /**
     * Get the default transcriber driver name.
     */
    public function getDefaultDriver(): string
    {
        return 'whisper';
    }
}
