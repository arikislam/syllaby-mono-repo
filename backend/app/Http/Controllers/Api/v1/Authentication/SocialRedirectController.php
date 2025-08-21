<?php

namespace App\Http\Controllers\Api\v1\Authentication;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Resources\RedirectUrlResource;
use Laravel\Socialite\Two\AbstractProvider;
use App\Http\Requests\Authentication\SocialLoginRequest;

class SocialRedirectController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function show(SocialLoginRequest $request, string $provider)
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        $url = $driver->stateless()
            ->setScopes(config("services.{$provider}.scopes"))
            ->with(config("services.{$provider}.params"))
            ->redirect()->getTargetUrl();

        return $this->respondWithResource(new RedirectUrlResource($url));
    }
}
