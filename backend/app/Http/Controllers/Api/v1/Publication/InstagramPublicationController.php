<?php

namespace App\Http\Controllers\Api\v1\Publication;

use Exception;
use InvalidArgumentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicationResource;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Http\Requests\Publication\InstagramPublicationRequest;
use App\Syllaby\Publisher\Publications\Actions\PublisherAction;
use App\Syllaby\Publisher\Publications\Concerns\TrackPublications;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;
use App\Syllaby\Publisher\Publications\Exceptions\PublicationFailedException;

class InstagramPublicationController extends Controller
{
    use TrackPublications;

    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(InstagramPublicationRequest $request, PublisherAction $action): JsonResponse|Response
    {
        if ($request->isStory()) {
            $request->ensureIsBusinessAccount();
        }

        try {
            $publication = $action->handle($request->validated(), SocialAccountEnum::Instagram->toString(), $request->publication, $request->channel);

            if (blank($publication)) {
                return response()->noContent();
            }

            $this->trackPublication($request);

            $publication->load('channels.account', 'event', 'media');

            return $this->respondWithResource(PublicationResource::make($publication), Response::HTTP_ACCEPTED);
        } catch (InvalidRefreshTokenException|InvalidArgumentException $exception) {
            return $this->respondWithArray(['channel_id' => $request->channel->id], Response::HTTP_BAD_REQUEST, $exception->getMessage());
        } catch (PublicationFailedException $exception) {
            return $this->respondWithArray(['channel_id' => $request->channel->id], Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getMessage());
        } catch (Exception $exception) {
            Log::error("Instagram Publication Failed - {$exception->getMessage()}");

            return $this->respondWithArray(['channel_id' => $request->channel->id], Response::HTTP_INTERNAL_SERVER_ERROR, __('publish.generic_error'));
        }
    }
}
