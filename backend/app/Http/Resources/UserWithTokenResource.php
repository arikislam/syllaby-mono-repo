<?php

namespace App\Http\Resources;

use Str;
use App\Syllaby\Users\User;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserWithTokenResource extends JsonResource
{
    protected string $token;

    public function __construct(User $resource)
    {
        parent::__construct($resource);

        $this->token = $resource->createToken(Str::limit(request()->userAgent(), 250))->plainTextToken;
    }

    public function toArray($request): array
    {
        return [
            'user' => UserResource::make($this->resource),
            'token' => $this->token,
        ];
    }
}
