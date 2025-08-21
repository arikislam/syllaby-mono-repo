<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Throwable;
use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Faceless;
use App\Http\Controllers\Controller;
use App\Http\Resources\FacelessResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Videos\RenderFacelessRequest;
use App\Syllaby\Videos\Actions\RenderFacelessAction;

class RenderFacelessController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(Faceless $faceless, RenderFacelessRequest $request, RenderFacelessAction $render)
    {
        $user = $this->user();

        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        try {
            $faceless = $render->handle($faceless, $user, $request->validated());
        } catch (Throwable) {
            return $this->errorInternalError('Whoops! Something went wrong.');
        }

        return $this->respondWithResource(FacelessResource::make($faceless->loadMissing('genre')), Response::HTTP_ACCEPTED);
    }
}
