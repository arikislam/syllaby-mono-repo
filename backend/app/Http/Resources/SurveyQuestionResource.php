<?php

namespace App\Http\Resources;

use App\Syllaby\Surveys\Question;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Question */
class SurveyQuestionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'survey_id' => $this->survey_id,

            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'placeholder' => $this->placeholder,
            'selected' => $this->selected,
            'options' => $this->options,
            'metadata' => $this->metadata,
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'answers' => SurveyAnswerResource::collection($this->whenLoaded('answers')),
        ];
    }
}
