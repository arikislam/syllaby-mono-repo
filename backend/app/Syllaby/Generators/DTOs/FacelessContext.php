<?php

namespace App\Syllaby\Generators\DTOs;

use Illuminate\Contracts\Support\Arrayable;

class FacelessContext implements Arrayable
{
    public function __construct(public ?array $topics, public ?int $explained = 0) {}

    public function getExplainedTopics(): array
    {
        if ($this->explained === 0 || ! $this->topics) {
            return [];
        }

        return array_slice($this->topics, 0, $this->explained + 1);
    }

    public static function fromArray(array $data): self
    {
        if (! isset($data['topics'], $data['explained'])) {
            return new self(null, 0);
        }

        return new self($data['topics'], $data['explained']);
    }

    public function toArray(): array
    {
        return [
            'topics' => $this->topics,
            'explained' => $this->explained,
        ];
    }
}
