<?php

namespace App\Http\Resources;

use App\Syllaby\Ideas\Idea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Idea */
class IdeaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'keyword_id' => $this->keyword_id,
            'keyword' => KeywordResource::make($this->whenLoaded('keyword')),

            'title' => $this->title,
            'slug' => $this->slug,
            'trend' => $this->trend,
            'country' => $this->country,
            'currency' => $this->currency,
            'locale' => $this->locale,
            'volume' => $this->volume,
            'cpc' => $this->cpc,
            'competition' => $this->competition,
            'competition_label' => $this->competition_label,
            'total_results' => $this->total_results,
            'trends' => $this->trends,
            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }
}
