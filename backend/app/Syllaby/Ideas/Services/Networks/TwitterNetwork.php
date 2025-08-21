<?php

namespace App\Syllaby\Ideas\Services\Networks;

use Illuminate\Support\Arr;
use App\Syllaby\Ideas\Keyword;
use App\Syllaby\Ideas\Enums\Networks;

class TwitterNetwork extends NetworkSearcher
{
    /**
     * Attempts to find ideas data for a given keyword.
     */
    public function handle(Keyword $keyword): void
    {
        $type = Networks::type($keyword->network->value);

        $this->getKeywordSuggestions($keyword, $type);
    }

    /**
     * Searches for keyword suggestions on Twitter.
     */
    protected function getKeywordSuggestions(Keyword $keyword, string $type): void
    {
        $response = $this->http()->post('/search/suggestions/twitter', [
            'apikey' => config('services.keywordtool.key'),
            'metrics_currency' => 'USD',
            'keyword' => $keyword->name,
            'type' => $type,
            'complete' => false,
            'metrics' => true,
            'language' => 'en',
            'output' => 'json',
        ]);

        if ($response->failed() || blank($response->json('results'))) {
            return;
        }

        foreach (array_chunk($response->json('results'), 4) as $chunk) {
            $this->upsertIdeas(Arr::flatten($chunk, 1), $keyword, $type);
        }
    }
}
