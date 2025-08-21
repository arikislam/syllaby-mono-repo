<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AiCompletionsResource extends JsonResource
{
    protected string $response;

    public function __construct(string $response)
    {
        parent::__construct([]);

        $this->response = $response;
    }

    public function toArray($request): array
    {
        return [
            'response' => $this->response
        ];
    }
}
