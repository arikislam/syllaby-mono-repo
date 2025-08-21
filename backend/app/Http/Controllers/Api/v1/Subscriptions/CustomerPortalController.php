<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\RedirectUrlResource;

class CustomerPortalController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function show(): JsonResponse
    {
        $user = $this->user();

        if (blank($user->stripe_id)) {
            return $this->errorForbidden('No customer found.');
        }

        $url = $user->billingPortalUrl(
            config('app.frontend_url').'/my-account/subscription'
        );

        return $this->respondWithResource(new RedirectUrlResource($url));
    }
}
