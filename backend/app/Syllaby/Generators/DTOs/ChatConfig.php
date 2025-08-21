<?php

namespace App\Syllaby\Generators\DTOs;

readonly class ChatConfig
{
    public function __construct(
        public ?string $model = null,
        public ?float $temperature = null,
        public ?float $topP = null,
        public ?float $presencePenalty = null,
        public ?float $frequencyPenalty = null,
        public ?array $responseFormat = null,
        public ?int $maxCompletionTokens = null,
    ) {}

    public static function forGPT(?self $config = null): self
    {
        return new self(
            model: $config?->model ?? config('openai.model'),
            topP: $config?->topP ?? config('openai.top_p'),
            presencePenalty: $config?->presencePenalty ?? 0,
            frequencyPenalty: $config?->frequencyPenalty ?? 0,
            responseFormat: $config?->responseFormat,
            maxCompletionTokens: $config?->maxCompletionTokens ?? config('openai.max_token'),
        );
    }

    public static function forGemini(?self $config = null): self
    {
        return new self(
            model: $config?->model ?? config('services.gemini.model'),
        );
    }

    public static function forXAi(?self $config = null): self
    {
        return new self(
            model: $config?->model ?? config('services.xai.model'),
        );
    }

    public static function forClaude(?self $config = null): self
    {
        return new self(
            model: $config?->model ?? config('services.claude.model'),
            maxCompletionTokens: $config?->maxCompletionTokens ?? config('services.claude.max_tokens'),
            temperature: $config?->temperature ?? config('services.claude.temperature'),
        );
    }

    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'temperature' => $this->temperature,
            'top_p' => $this->topP,
            'presence_penalty' => $this->presencePenalty,
            'frequency_penalty' => $this->frequencyPenalty,
            'response_format' => $this->responseFormat,
            'max_completion_tokens' => $this->maxCompletionTokens,
        ];
    }
}
