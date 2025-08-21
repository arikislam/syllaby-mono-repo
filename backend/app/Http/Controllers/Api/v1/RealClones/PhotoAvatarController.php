<?php

namespace App\Http\Controllers\Api\v1\RealClones;

use Laravel\Pennant\Feature;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\AvatarResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\RealClones\CreatePhotoAvatarRequest;
use App\Syllaby\RealClones\Actions\CreatePhotoAvatarAction;

class PhotoAvatarController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Uploads and creates a user photo avatar.
     */
    public function store(CreatePhotoAvatarRequest $request, CreatePhotoAvatarAction $create): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $provider = $request->validated('provider', 'd-id');
        $input = [...$request->validated(), 'face' => $request->input('face')];

        if (! $avatar = $create->handle($this->user(), $provider, $input)) {
            return $this->errorInternalError('Whoops! It was not possible to create the photo avatar.');
        }

        return $this->respondWithResource(AvatarResource::make($avatar), Response::HTTP_CREATED);
    }
}
