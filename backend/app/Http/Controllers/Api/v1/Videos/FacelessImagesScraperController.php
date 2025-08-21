<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Log;
use Exception;
use App\Syllaby\Videos\Faceless;
use App\Http\Controllers\Controller;
use App\Http\Resources\FacelessResource;
use App\Http\Requests\Scraper\ScrapeImagesRequest;
use App\Syllaby\Scraper\Actions\ExtractImagesAction;

class FacelessImagesScraperController extends Controller
{
    public function __construct()
    {
        $this->middleware(['subscribed', 'auth:sanctum']);
    }

    public function store(ScrapeImagesRequest $request, Faceless $faceless, ExtractImagesAction $action)
    {
        try {
            $faceless = $action->handle($faceless, $request->only('url'));

            return $this->respondWithResource(FacelessResource::make($faceless->loadMissing('assets.media')));
        } catch (Exception $e) {
            Log::error('Error extracting images for faceless video', ['error' => $e->getTraceAsString()]);

            return $this->errorInternalError('We were unable to analyse this url. Please try again with a different url.');
        }
    }
}
