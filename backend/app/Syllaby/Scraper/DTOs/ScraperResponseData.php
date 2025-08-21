<?php

namespace App\Syllaby\Scraper\DTOs;

use Arr;

final readonly class ScraperResponseData
{
    public function __construct(
        public string $url,
        public string $title,
        public array $response,
        public string $content
    ) {}

    public static function fromResponse(array $response, string $format): self
    {
        return new self(
            url: Arr::get($response, 'data.metadata.url'),
            title: implode(' ', Arr::wrap(Arr::get($response, 'data.metadata.title'))),
            response: $response,
            content: Arr::get($response, "data.{$format}")
        );
    }
}
