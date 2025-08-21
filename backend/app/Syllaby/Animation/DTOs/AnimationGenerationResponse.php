<?php

namespace App\Syllaby\Animation\DTOs;

use Arr;
use App\Syllaby\Assets\Enums\AssetProvider;
use App\Syllaby\Animation\Enums\MinimaxStatus;

final readonly class AnimationGenerationResponse
{
    public function __construct(
        public string $id,
        public string $provider,
        public string $model,
        public ?string $description = null,
        public MinimaxStatus $status,
    ) {}

    public static function fromMinimax(array $response, array $params = []): self
    {
        $code = (int) Arr::get($response, 'base_resp.status_code');

        return new self(
            id: Arr::get($response, 'task_id'),
            provider: AssetProvider::MINIMAX->value,
            model: Arr::get($params, 'model', 'video-01'),
            description: Arr::get($params, 'description'),
            status: MinimaxStatus::from($code),
        );
    }
}
