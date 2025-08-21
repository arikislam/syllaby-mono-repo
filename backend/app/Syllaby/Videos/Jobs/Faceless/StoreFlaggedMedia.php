<?php

namespace App\Syllaby\Videos\Jobs\Faceless;

use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class StoreFlaggedMedia implements ShouldQueue
{
    use Queueable;

    public function __construct(protected ?string $url) {}

    public function handle(): void
    {
        if (! $this->url) {
            return;
        }

        $extension = pathinfo($this->url, PATHINFO_EXTENSION);

        $filename = sprintf('moderation/gemini-out-%s.%s', md5($this->url), $extension);

        Storage::disk('assets')->put($filename, file_get_contents($this->url), [
            'ACL' => 'public-read',
            'ContentType' => 'image/jpeg',
        ]);
    }
}
