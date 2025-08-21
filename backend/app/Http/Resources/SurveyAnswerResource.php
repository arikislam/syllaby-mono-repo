<?php

namespace App\Http\Resources;

use App\Syllaby\Surveys\Answer;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Answer */
class SurveyAnswerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'answer' => $this->body,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
