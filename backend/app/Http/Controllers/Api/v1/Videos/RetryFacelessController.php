<?php

namespace App\Http\Controllers\Api\v1\Videos;

use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\FacelessResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Videos\RetryFacelessRequest;
use App\Syllaby\Videos\Actions\RetryFacelessAction;

class RetryFacelessController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(RetryFacelessRequest $request, Faceless $faceless, RetryFacelessAction $action)
    {
        $this->authorize('retry', $faceless);

        $faceless = $action->handle($faceless, $this->user());

        return $this->respondWithResource(FacelessResource::make($faceless), Response::HTTP_ACCEPTED);
    }
}
