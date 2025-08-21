<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Exception;
use App\Syllaby\Videos\Faceless;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\FacelessResource;
use App\Http\Requests\Scraper\ScrapeUrlRequest;
use App\Syllaby\Scraper\Actions\ExtractScriptAction;

class FacelessScraperController extends Controller
{
    const string SCRAPE_THROTTLE_PREFIX = 'scrape-attempt:';

    public function __construct()
    {
        $this->middleware(['subscribed', 'auth:sanctum']);
    }

    public function store(ScrapeUrlRequest $request, Faceless $faceless, ExtractScriptAction $action): JsonResponse
    {
        $this->authorize('update', $faceless);

        $request->ensureIsThrottled(static::SCRAPE_THROTTLE_PREFIX, config('services.firecrawl.rate_limit_attempts'), 'firecrawl');

        try {
            $faceless = $action->handle($faceless, $request->validated());

            return $this->respondWithResource(FacelessResource::make($faceless));
        } catch (Exception $exception) {
            Log::error('Error extracting script for faceless video', ['error' => $exception->getMessage()]);

            return $this->errorInternalError($exception->getMessage());
        }
    }
}
