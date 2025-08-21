<?php

namespace App\Syllaby\Scraper\Jobs;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Scraper\ScraperLog;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogScraperResponse implements ShouldQueue
{
    use Queueable;

    public function __construct(protected array $response, protected string $url, protected User $user, protected string $format) {}

    public function handle(): void
    {
        ScraperLog::create([
            'user_id' => $this->user->id,
            'url' => $this->url,
            'title' => implode(' ', Arr::wrap(Arr::get($this->response, 'data.metadata.title'))),
            'response' => $this->response,
            'provider' => 'FireCrawl', // Only using FireCrawl for now
            'format' => $this->format,
            'content' => Arr::get($this->response, 'data.'.$this->format),
            'status' => Arr::get($this->response, 'data.metadata.statusCode') ?? 500,
        ]);
    }
}
