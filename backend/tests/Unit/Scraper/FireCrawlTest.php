<?php

use App\Syllaby\Users\User;
use App\Syllaby\Scraper\Vendors\FireCrawl;
use App\Syllaby\Scraper\DTOs\ScraperResponseData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('should scrape and return a DTO', function () {
    Http::fake([
        '*' => Http::response([
            'data' => [
                'metadata' => [
                    'url' => 'https://example.com',
                    'title' => 'Example title',
                    'statusCode' => 200,
                ],
                'rawHtml' => '<html><head><title>Example</title></head</html>',
            ],
        ]),
    ]);

    $scraper = new FireCrawl(User::factory()->create());
    $response = $scraper->scrape('https://example.com');

    expect($response)
        ->toBeInstanceOf(ScraperResponseData::class)
        ->url->toBe('https://example.com')
        ->title->toBe('Example title')
        ->content->toBe('<html><head><title>Example</title></head</html>');
});
