<?php

namespace App\Syllaby\Generators\Contracts;

use App\Syllaby\Assets\Enums\AssetProvider;
use App\Syllaby\Generators\DTOs\ImageGeneratorResponse;

interface ImageGenerator
{
    /**
     * Triggers an image generation.
     */
    public function image(array $options, ?string $prompt = null): ?ImageGeneratorResponse;

    /**
     * Triggers an async image generation.
     */
    public function async(): self;

    /**
     * Get the provider name.
     */
    public function provider(): AssetProvider;
}
