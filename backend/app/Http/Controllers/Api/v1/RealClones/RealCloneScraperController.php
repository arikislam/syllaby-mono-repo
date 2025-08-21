<?php

namespace App\Http\Controllers\Api\v1\RealClones;

use Exception;
use App\Http\Controllers\Controller;
use App\Syllaby\RealClones\RealClone;
use App\Http\Resources\RealCloneResource;
use App\Http\Requests\Scraper\ScrapeUrlRequest;
use App\Syllaby\Scraper\Actions\ExtractScriptAction;

class RealCloneScraperController extends Controller
{
    const string SCRAPE_THROTTLE_PREFIX = 'scrape-attempt:';

    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(ScrapeUrlRequest $request, RealClone $clone, ExtractScriptAction $action)
    {
        $this->authorize('update', $clone);

        $request->ensureIsThrottled(static::SCRAPE_THROTTLE_PREFIX, config('services.firecrawl.rate_limit_attempts'), 'firecrawl');

        try {
            $clone = $action->handle($clone, $request->validated());

            return $this->respondWithResource(RealCloneResource::make($clone));
        } catch (Exception $e) {
            return $this->errorInternalError($e->getMessage());
        }
    }
}
