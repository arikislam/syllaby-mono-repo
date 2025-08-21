<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Ideas\Keyword;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Keyword */
class KeywordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'network' => $this->network,
            'source' => $this->source,

            $this->mergeWhen($request->has('metrics'), [
                'metrics' => [
                    'trend' => (float) $this->resource->trend_avg,
                    'cpc' => [
                        'min' => (float) $this->resource->cpc_min,
                        'max' => (float) $this->resource->cpc_max,
                    ],
                    'volume' => [
                        'min' => (int) $this->resource->volume_min,
                        'max' => (int) $this->resource->volume_max,
                    ],
                    'competition' => [
                        'min' => (float) $this->resource->competition_min,
                        'max' => (float) $this->resource->competition_max,
                    ],
                ],
            ]),

            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }
}
