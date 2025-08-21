<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RedirectUrlResource extends JsonResource
{
    protected string $url;

    public function __construct(string $url)
    {
        parent::__construct([]);

        $this->url = $url;
    }

    public function toArray($request): array
    {
        return [
            'redirect_url' => $this->url
        ];
    }
}
