<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;

/** @mixin PublicationMetricValue */
class MetricResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->value,
            'date' => $this->created_at->toJSON(),
        ];
    }
}
