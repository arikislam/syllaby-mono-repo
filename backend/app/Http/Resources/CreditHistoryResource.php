<?php

namespace App\Http\Resources;

use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CreditHistory */
class CreditHistoryResource extends JsonResource
{
    public function toArray($request): array
    {
        $spent = $this->event_type !== 1;

        return [
            'label' => $this->label,
            'content_type' => CreditEventEnum::tryFrom($this->description)?->details() ?? $this->creditable_type,
            'credit_spend' => sprintf("%s%s", $spent ? '-' : '+', $this->amount),
            'transaction_type' => $spent ? 'Debit' : 'Credit',
            'meta' => $this->meta,
            'created_at' => $this->created_at,
        ];
    }
}
