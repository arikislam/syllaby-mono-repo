<?php

namespace App\Syllaby\Generators\DTOs;

use Illuminate\Support\Arr;
use App\Syllaby\Assets\Enums\AssetProvider;
use Illuminate\Contracts\Support\Arrayable;

readonly class ImageGeneratorResponse implements Arrayable
{
    public function __construct(
        public string $id,
        public string $provider,
        public string $status,
        public ?string $model = null,
        public ?string $description = null,
        public ?string $url = null,
    ) {}

    public static function fromReplicate(array $response, ?string $prompt = null): self
    {
        $url = Arr::first((array) Arr::get($response, 'output')) ?? null;

        return new self(
            id: Arr::get($response, 'id'),
            provider: AssetProvider::REPLICATE->value,
            status: Arr::get($response, 'status'),
            model: sprintf('%s:%s', Arr::get($response, 'model'), Arr::get($response, 'version')),
            description: $prompt ?? Arr::get($response, 'input.prompt'),
            url: $url,
        );
    }

    public static function fromSyllaby(array $response, ?string $prompt = null): self
    {
        return new self(
            id: Arr::get($response, 'id'),
            provider: AssetProvider::SYLLABY->value,
            status: Arr::get($response, 'status'),
            model: Arr::get($response, 'model_id'),
            description: $prompt ?? Arr::get($response, 'input.prompt'),
            url: Arr::first((array) Arr::get($response, 'output_urls')) ?? null,
        );
    }

    public function toArray(): array
    {
        return (array) $this;
    }
}
