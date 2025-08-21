<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Faceless;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\FacelessResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Videos\ExportFacelessRequest;
use App\Syllaby\Videos\Actions\ExportFacelessAction;
use App\Syllaby\Videos\Jobs\Faceless\BuildFacelessVideoSource;

class ExportFacelessController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Handles the export process of the edited faceless video.
     */
    public function store(ExportFacelessRequest $request, Faceless $faceless, ExportFacelessAction $export): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        if (! $faceless = $export->handle($faceless, $this->user(), $request->validated())) {
            return $this->errorInternalError();
        }

        dispatch(new BuildFacelessVideoSource($faceless));

        return $this->respondWithResource(FacelessResource::make($faceless), Response::HTTP_ACCEPTED);
    }
}
