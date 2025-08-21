<?php

namespace App\Syllaby\Ideas\Services\Networks;

use Illuminate\Support\Arr;
use App\Syllaby\Ideas\Keyword;
use App\Syllaby\Ideas\Enums\Networks;

class BingNetwork extends NetworkSearcher
{
    /**
     * Attempts to find ideas SEO data for a given keyword.
     */
    public function handle(Keyword $keyword): void
    {
        $type = Networks::type($keyword->network->value);

        $this->getKeywordSuggestions($keyword, $type);
    }

    /**
     * Searches for keyword suggestions on Bing.
     */
    protected function getKeywordSuggestions(Keyword $keyword, string $type): void
    {
        $response = $this->http()->post('/search/suggestions/bing', [
            'metrics_network' => 'ownedandoperatedandsyndicatedsearch',
            'apikey' => config('services.keywordtool.key'),
            'metrics_language' => ['English'],
            'metrics_location' => [190],
            'keyword' => $keyword->name,
            'type' => $type,
            'category' => 'all',
            'complete' => false,
            'metrics' => true,
            'language' => 'en',
            'output' => 'json',
            'country' => 'US',
        ]);

        if ($response->failed() || blank($response->json('results'))) {
            return;
        }

        foreach (array_chunk($response->json('results'), 4) as $chunk) {
            $this->upsertIdeas(Arr::flatten($chunk, 1), $keyword, $type);
        }
    }
}
