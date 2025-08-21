<?php

namespace App\Http\Controllers\Api\v1\Publication;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Publisher\Publications\Concerns\TrackPublications;
use App\Http\Resources\PublicationResource;
use App\Http\Requests\Publication\DraftPublicationRequest;
use App\Syllaby\Publisher\Publications\Actions\DraftPublicationAction;

class DraftPublicationController extends Controller
{
    use TrackPublications;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(DraftPublicationRequest $request, DraftPublicationAction $action): JsonResponse
    {
        if (! $publication = $action->handle($request->validated())) {
            return $this->errorInternalError('Whoops! Something went wrong.');
        }

        $publication->load('channels.account');

        return $this->respondWithResource(PublicationResource::make($publication));
    }
}
