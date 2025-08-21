<?php

namespace App\Syllaby\Animation\Contracts;

use App\Syllaby\Videos\Faceless;
use App\Syllaby\Animation\DTOs\AnimationStatusData;
use App\Syllaby\Animation\DTOs\AnimationGenerationResponse;

interface AnimationGenerator
{
    /**
     * Initiate the animation generation process.
     */
    public function generate(Faceless $faceless, string $mediaUrl, ?string $prompt = null): AnimationGenerationResponse;

    /**
     * Get the status of the animation generation process.
     */
    public function status(int $identifier): AnimationStatusData;

    /**
     * Get the download link for the generated animation.
     */
    public function getDownloadUrl(int $identifier): string;
}
