<?php

namespace App\Http\Controllers\Api\v1\Metadata;

use Log;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Http\Requests\Metadata\CreatorInfoRequest;
use App\Syllaby\Publisher\Publications\Actions\TikTokCreatorInfoAction;

class TikTokCreatorInfoController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function index(CreatorInfoRequest $request, TikTokCreatorInfoAction $action): JsonResponse
    {
        try {
            $channel = SocialChannel::query()->find($request->validated('id'));
            $data = $action->handle($channel->account);

            return $this->respondWithArray($data->toArray());
        } catch (Exception $exception) {
            Log::debug('TikTok Request Failed to fetch Creator Information', [$exception->getMessage()]);

            return $this->errorInternalError('Sorry! We were unable to fetch your profile details');
        }
    }
}
