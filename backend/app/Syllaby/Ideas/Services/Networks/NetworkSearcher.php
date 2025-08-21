<?php

namespace App\Syllaby\Ideas\Services\Networks;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Ideas\Idea;
use App\Syllaby\Ideas\Keyword;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

abstract class NetworkSearcher
{
    /**
     * Handles each network specifics search methods.
     */
    public abstract function handle(Keyword $keyword): void;

    /**
     * Searches for keyword suggestions on given network.
     */
    protected abstract function getKeywordSuggestions(Keyword $keyword, string $type): void;

    /**
     * Setup for the KeywordTool client.
     */
    protected function http(): PendingRequest
    {
        return Http::asJson()->timeout(120)->baseUrl(config('services.keywordtool.url'));
    }

    /**
     * Creates or updates the given ideas.
     */
    protected function upsertIdeas(array $results, Keyword $keyword, string $type): void
    {
        Idea::upsert(
            values: $this->parseIdeas($results, $keyword, $type),
            uniqueBy: ['keyword_id', 'slug'],
            update: ['trend', 'type', 'trends', 'volume', 'cpc', 'competition', 'competition_label', 'valid_until']
        );
    }

    /**
     * Maps the api volume response to the database structure.
     */
    protected function parseIdeas(array $results, Keyword $keyword, string $type): array
    {
        return Arr::map(array_values($results), fn ($result) => [
            'type' => $type,
            'locale' => 'en',
            'currency' => 'USD',
            'country' => 'USA',
            'keyword_id' => $keyword->id,
            'valid_until' => now()->addWeeks(3),
            'title' => Arr::get($result, 'string'),
            'trends' => $this->buildTrends($result),
            'cpc' => Arr::get($result, 'cpc', 0) ?? 0,
            'trend' => Arr::get($result, 'trend', 0) ?? 0,
            'volume' => Arr::get($result, 'volume', 0) ?? 0,
            'slug' => Str::slug(Arr::get($result, 'string')),
            'competition' => $this->competitionValue(Arr::get($result, 'cmp', 0) ?? 0),
            'competition_label' => $this->competitionLabel(Arr::get($result, 'cmp', 0) ?? 0),
        ]);
    }

    /**
     * Extract the monthly values for the suggestion tend chart.
     */
    protected function buildTrends(array $data): string
    {
        $months = array_map(fn ($month) => 'm' . $month, range(1, 12));
        $values = Arr::map(Arr::only($data, $months), fn ($value, $key) => $value ?? 0);

        return implode(',', array_values($values));
    }

    /**
     * Gets a float competition value even when the field is a string.
     */
    protected function competitionValue(mixed $competition): float
    {
        if (is_numeric($competition)) {
            return $competition;
        }

        $rdm = fn ($min, $max) => $min + mt_rand() / mt_getrandmax() * ($max - $min);

        return match (Str::lower($competition)) {
            'low' => $rdm(0, 0.34),
            'medium' => $rdm(0.35, 0.69),
            default => $rdm(0.70, 1.00),
        };
    }

    /**
     * Get a competition label even when the field is a float
     */
    protected function competitionLabel(mixed $competition): string
    {
        if (is_string($competition)) {
            return Str::lower($competition);
        }

        return match (true) {
            ($competition <= 0.34) => 'low',
            ($competition <= 0.69) => 'medium',
            default => 'high'
        };
    }
}
