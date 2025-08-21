<?php

namespace App\Http\Controllers\Api\v1\Channels;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\SocialChannelResource;
use App\Syllaby\Publisher\Channels\SocialChannel;

class SocialAccountController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(): JsonResponse
    {
        $accounts = SocialChannel::whereRelation('account', 'user_id', $this->user()->id)
            ->with('account')
            ->get();

        return $this->respondWithResource(SocialChannelResource::collection($accounts));
    }
}
