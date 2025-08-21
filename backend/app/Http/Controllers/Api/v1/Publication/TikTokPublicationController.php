<?php

namespace App\Http\Controllers\Api\v1\Publication;

use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicationResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Publication\TikTokPublicationRequest;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Publications\Actions\PublisherAction;
use App\Syllaby\Publisher\Publications\Concerns\TrackPublications;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;
use App\Syllaby\Publisher\Publications\Exceptions\PublicationFailedException;

class TikTokPublicationController extends Controller
{
    use TrackPublications;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(TikTokPublicationRequest $request, PublisherAction $action): JsonResponse|Response
    {
        try {
            $publication = $action->handle($request->validated(), SocialAccountEnum::TikTok->toString(), $request->publication, $request->channel);

            if (blank($publication)) {
                return response()->noContent();
            }

            $this->trackPublication($request);

            $publication->load('channels.account', 'event');

            return $this->respondWithResource(PublicationResource::make($publication), Response::HTTP_ACCEPTED);
        } catch (InvalidRefreshTokenException) {
            return $this->respondWithArray(['channel_id' => $request->channel->id], Response::HTTP_FORBIDDEN, __('publish.lost_permission'));
        } catch (PublicationFailedException $e) {
            return $this->respondWithArray(['channel_id' => $request->channel->id], Response::HTTP_INTERNAL_SERVER_ERROR, "Publishing failed. Reason: {$e->getMessage()}");
        } catch (Throwable $exception) {
            Log::debug("Unable to post on TikTok. Reason: {$exception->getMessage()}");

            return $this->respondWithArray(['channel_id' => $request->channel->id], Response::HTTP_INTERNAL_SERVER_ERROR, __('publish.generic_error'));
        }
    }
}
