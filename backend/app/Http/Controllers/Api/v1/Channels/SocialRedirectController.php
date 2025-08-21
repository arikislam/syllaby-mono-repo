<?php

namespace App\Http\Controllers\Api\v1\Channels;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\RedirectUrlResource;
use App\Syllaby\Publisher\Channels\Actions\RedirectAction;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

class SocialRedirectController extends Controller
{
    const string REDIRECT_URL_PATTERN = '/https:\/\/(dev-ai|stg-ai|ai)\.syllaby\.(dev|io)\/(social-connect\/(linkedin|tiktok|youtube|facebook|instagram|threads)|calendar|mobile\/auth\/(youtube))/';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function show(string $provider, RedirectAction $action): JsonResponse
    {
        $redirectUrl = request()->query('redirect_url', 'invalid-url');

        if (! preg_match(self::REDIRECT_URL_PATTERN, $redirectUrl)) {
            return $this->errorWrongArgs(__('social.invalid_url'));
        }

        try {
            $url = $action->handle(SocialAccountEnum::fromString($provider), $redirectUrl);

            return $this->respondWithResource(new RedirectUrlResource($url));
        } catch (Exception $e) {
            return $this->errorNotFound($e->getMessage());
        }
    }
}
