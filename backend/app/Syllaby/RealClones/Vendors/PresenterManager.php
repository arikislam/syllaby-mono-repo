<?php

namespace App\Syllaby\RealClones\Vendors;

use Exception;
use Illuminate\Support\Manager;
use App\Syllaby\RealClones\Contracts\PresenterContract;

class PresenterManager extends Manager
{
    /**
     * Instantiates a D-ID driver.
     */
    public function createDiDDriver(): PresenterContract
    {
        return new DiD();
    }

    /**
     * Instantiates a Heygen driver.
     */
    public function createHeygenDriver(): PresenterContract
    {
        return new Heygen();
    }

    /**
     * Instantiates a Heygen driver.
     */
    public function createFastvideoDriver(): PresenterContract
    {
        return new FastVideo();
    }

    /**
     * Get the default render engine driver name.
     *
     * @throws Exception
     */
    public function getDefaultDriver(): string
    {
        throw new Exception('No default real clone presenter driver.');
    }
}
