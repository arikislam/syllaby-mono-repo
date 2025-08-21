<?php

namespace App\Syllaby\Publisher\Channels\Commands;

use Throwable;
use Illuminate\Console\Command;
use App\Syllaby\Metadata\Metadata;
use Symfony\Component\Console\Command\Command as Symfony;
use App\Syllaby\Publisher\Channels\Services\Youtube\MetadataService;

class RefreshYoutubeCategories extends Command
{
    protected $signature = 'youtube:categories';

    protected $description = 'Populate the Youtube categories';

    /** @throws Throwable */
    public function handle(MetadataService $service): int
    {
        attempt(function () use ($service) {
            $this->deleteExistingCategories();
            $service->categories();
        });

        return Symfony::SUCCESS;
    }

    public function deleteExistingCategories(): void
    {
        Metadata::where('provider', 'youtube')
            ->where('type', 'social-upload')
            ->where('key', 'categories')
            ->delete();
    }
}
