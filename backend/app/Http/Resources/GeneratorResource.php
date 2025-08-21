<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Generators\Generator;
use App\Syllaby\Generators\DTOs\FacelessContext;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Generator */
class GeneratorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'model_id' => $this->model_id,
            'model_type' => $this->model_type,
            'topic' => $this->topic,
            'length' => $this->length,
            'tone' => $this->tone,
            'style' => $this->style,
            'language' => $this->language,
            'context' => $this->parseContext(),
            'output' => $this->output,
            'created_at' => $this->created_at->toJson(),
            'updated_at' => $this->updated_at->toJson(),
        ];
    }

    private function parseContext(): array
    {
        if (! $this->context) {
            return [];
        }

        if ($this->model_type === (new Faceless)->getMorphClass()) {
            return FacelessContext::fromArray($this->context)->getExplainedTopics();
        }

        return $this->context;
    }
}
