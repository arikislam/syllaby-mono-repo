<?php

namespace App\Syllaby\Videos\Vendors\Renders;

use Illuminate\Support\Manager;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Videos\Contracts\RenderContract;

class RenderManager extends Manager
{
    /**
     * Instantiates a Creatomate driver.
     */
    public function createCreatomateDriver(): RenderContract
    {
        $client = Http::acceptJson()
            ->baseUrl(config('services.creatomate.url'))
            ->withToken(config('services.creatomate.key'));

        return new Creatomate($client);
    }

    /**
     * Get the default render engine driver name.
     */
    public function getDefaultDriver(): string
    {
        return 'creatomate';
    }
}
