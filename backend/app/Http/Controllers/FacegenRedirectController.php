<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\RedirectUrlResource;

class FacegenRedirectController extends Controller
{
    const int EXPIRES_IN = 15;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function __invoke(Request $request)
    {
        $email = $this->user()->email;

        $url = config('services.facegen.url')."?email={$email}";

        return $this->respondWithResource(RedirectUrlResource::make($url))->cookie('email', $email, self::EXPIRES_IN, null, config('session.domain'), false, true);
    }
}
