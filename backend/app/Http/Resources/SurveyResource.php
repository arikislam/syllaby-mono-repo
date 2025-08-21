<?php

namespace App\Http\Resources;

use App\Syllaby\Surveys\Survey;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Survey */
class SurveyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'questions' => SurveyQuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
