<?php

namespace App\Syllaby\Videos\DTOs;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ModeratorData
{
    public function __construct(public bool $flagged, public array $violations = []) {}

    public static function fromGemini(array $results): self
    {
        $response = Arr::get($results, 'candidates.0.content.parts.0.text', 'no');

        if (Str::lower($response) === 'yes' || Arr::has($results, 'promptFeedback.blockReason')) {
            return new self(flagged: true);
        }

        return new self(flagged: false);
    }

    public static function fromOpenAi(array $results): self
    {
        if (Arr::get($results, 'results.0.flagged') === false) {
            return new self(flagged: false);
        }

        $categories = array_filter(Arr::get($results, 'results.0.categories'));
        $scores = array_filter(Arr::get($results, 'results.0.category_scores'));

        $violations = collect($categories)->keys()->filter(
            fn ($category) => Arr::get($scores, $category, 0) >= 0.56
        );

        if ($violations->isEmpty()) {
            return new self(flagged: false);
        }

        return new self(flagged: true, violations: $violations->toArray());
    }

    public static function fromReplicate(array $results): self
    {
        $isNsfw = (bool) Arr::get($results, 'output.nsfw_detected', false);

        if (! $isNsfw) {
            return new self(flagged: false);
        }

        return new self(flagged: true, violations: Arr::get($results, 'output.nsfw', []));
    }

    public function isNSFW(): bool
    {
        return $this->flagged;
    }
}
