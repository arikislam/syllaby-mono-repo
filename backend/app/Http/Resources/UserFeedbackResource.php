<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Surveys\UserFeedback;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserFeedback */
class UserFeedbackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'reason' => $this->reason,
            'details' => $this->details,
            'created_at' => $this->created_at->toJson(),
            'updated_at' => $this->updated_at->toJson(),
        ];
    }
}
