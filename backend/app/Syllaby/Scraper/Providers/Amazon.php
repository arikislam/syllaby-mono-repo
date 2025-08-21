<?php

namespace App\Syllaby\Scraper\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Purify\Facades\Purify;
use App\Syllaby\Scraper\Vendors\FireCrawl;
use App\Syllaby\Scraper\Contracts\Throttleable;
use App\Syllaby\Scraper\Contracts\ContentProvider;
use App\Syllaby\Scraper\Contracts\ScraperContract;

class Amazon implements ContentProvider, Throttleable
{
    public function __construct(protected ScraperContract $scraper) {}

    public function extractImages(string $url): array
    {
        $html = $this->getRawContent($url);

        if (! str_contains($html, 'id="extracted-amazon-data"')) {
            return [];
        }

        return $this->extractFromVariations($html);

    }

    public function extractContent(string $url): string
    {
        $raw = $this->getRawContent($url);

        return Purify::config(['HTML.Allowed' => ''])->clean($raw);
    }

    public function supports(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host === 'www.amazon.com' || Str::contains($host, 'amazon.com');
    }

    public function getThrottlingConfig(): array
    {
        return [
            'key' => FireCrawl::CACHE_KEY,
            'limit' => config('services.firecrawl.rate_limit_attempts'),
            'service' => 'firecrawl',
        ];
    }

    /**
     * Get raw HTML content from cache or scrape the URL
     */
    private function getRawContent(string $url): string
    {
        return $this->scraper->scrape($url, 'rawHtml', [
            'includeTags' => ['script'],
            'actions' => [
                ['type' => 'wait', 'milliseconds' => 1000],
                ['type' => 'executeJavascript', 'script' => '(()=>{const s=document.querySelectorAll("script");const filtered=Array.from(s).filter(script=>script.textContent&&(script.textContent.includes("ImageBlockATF")||script.textContent.includes("ImageBlockBTF")));if(filtered.length===0)return;const t=filtered[0];const m=t.textContent.match(/var data = ({[\\s\\S]*?});/);if(!m)return;try{const d=eval("("+m[1]+")");const e=document.createElement("div");e.id="extracted-amazon-data";e.style.display="none";e.textContent=JSON.stringify(d);document.body.appendChild(e)}catch(err){}})()'],
                ['type' => 'scrape'],
            ],
        ])->content;
    }

    /**
     * Extract images from variations JSON block.
     */
    private function extractFromVariations(string $html): array
    {
        $boundaries = max(
            strrpos($html, '<body>') ?: 0,
            strrpos($html, '</head>') ?: 0
        );

        $content = $boundaries > 0 ? substr($html, $boundaries) : $html;

        if (! preg_match('/<div id="extracted-amazon-data"[^>]*>([^<]+)<\/div>/', $content, $match)) {
            return [];
        }

        $json = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (empty($json) || json_validate($json) === false) {
            return [];
        }

        $data = json_decode($json, true);

        if (! isset($data['colorImages']['initial'])) {
            return [];
        }

        $default = array_keys(Arr::get($data, 'colorToAsin'))[0];
        $variants = Arr::get($data, "colorImages.{$default}", []);

        return collect($variants)
            ->pluck('large')
            ->filter(fn ($url) => filled($url) && Str::startsWith($url, 'http'))
            ->values()
            ->all();
    }
}
