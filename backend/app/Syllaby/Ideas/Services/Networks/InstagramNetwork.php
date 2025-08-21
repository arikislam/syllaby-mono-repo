<?php

namespace App\Syllaby\Ideas\Services\Networks;

use App\Syllaby\Ideas\Keyword;
use App\Syllaby\Ideas\Enums\Networks;

class InstagramNetwork extends NetworkSearcher
{
    /**
     * Attempts to find ideas for a given keyword.
     */
    public function handle(Keyword $keyword): void
    {
        $type = Networks::type($keyword->network->value);

        $this->getKeywordSuggestions($keyword, $type);
    }

    /**
     * Searches for keyword suggestions on Instagram.
     */
    protected function getKeywordSuggestions(Keyword $keyword, string $type): void
    {
        $response = $this->http()->post('/search/suggestions/instagram', [
            'apikey' => config('services.keywordtool.key'),
            'keyword' => $keyword->name,
            'metrics_currency' => 'USD',
            'type' => $type,
            'complete' => false,
            'metrics' => true,
            'language' => 'en',
            'output' => 'json',
        ]);

        if ($response->failed() || blank($response->json('results'))) {
            return;
        }

        collect($response->json('results'))->flatten(1)->chunk(100)->each(function ($chunk) use ($keyword, $type) {
            $this->upsertIdeas($chunk->toArray(), $keyword, $type);
        });
    }
}
