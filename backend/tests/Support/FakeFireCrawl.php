<?php

namespace Tests\Support;

use App\Syllaby\Scraper\DTOs\ScraperResponseData;
use App\Syllaby\Scraper\Contracts\ScraperContract;

class FakeFireCrawl implements ScraperContract
{
    public static string $html = '
        <html>
            <body>
                <h1>Test Product</h1>
                <div id="imageBlockVariations_feature_div">
                    <script>
                        var obj = jQuery.parseJSON(\'{"landingAsinColor": "red", "colorImages": {"red": [{"hiRes": "https://amazon.com/images/1.jpg"},{"hiRes": "https://amazon.com/images/2.jpg"}]}}\')</script>
                    </div>
                    <div class="description">
                        This is a test product description that will be used to generate the script.
                        It has multiple features and benefits that make it a great choice.
                        Perfect for those looking for quality and reliability.
                    </div>
                    <h1>Lorem Ipsum</h1>
                    <h2>Color Met</h2>
                    <p>One more tag</p>
                </body>
            </html>
        ';

    public function scrape(string $url, string $format, array $options = [], bool $fresh = false): ScraperResponseData
    {
        return new ScraperResponseData(
            url: $url,
            title: 'Test Product Title',
            response: [
                'data' => [
                    'metadata' => [
                        'url' => $url,
                        'title' => ['Test Product Title'],
                    ],
                    'rawHtml' => static::$html,
                ],
            ],
            content: static::$html
        );
    }

    public static function setHtml(string $html): void
    {
        static::$html = $html;
    }
}
