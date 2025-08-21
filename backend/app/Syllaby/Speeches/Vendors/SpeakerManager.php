<?php

namespace App\Syllaby\Speeches\Vendors;

use Illuminate\Support\Manager;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Speeches\Contracts\SpeakerContract;

class SpeakerManager extends Manager
{
    /**
     * Instantiates a Elevenlabs driver.
     */
    public function createElevenlabsDriver(): SpeakerContract
    {
        $client = Http::acceptJson()
            ->baseUrl(config('services.elevenlabs.url'))
            ->withHeaders(['xi-api-key' => config('services.elevenlabs.key')]);

        return new Elevenlabs($client);
    }

    /**
     * Get the default render engine driver name.
     */
    public function getDefaultDriver(): string
    {
        return 'elevenlabs';
    }
}
