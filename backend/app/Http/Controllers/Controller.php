<?php

namespace App\Http\Controllers;

use App\Syllaby\Users\User;
use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, ApiResponse;

    private function isAuthenticated(): bool
    {
        return auth('sanctum')->check();
    }

    protected function user(): ?User
    {
        if (! $this->isAuthenticated()) {
            return null;
        }

        return auth('sanctum')->user();
    }
}
