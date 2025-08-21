<?php

namespace App\Syllaby\Publisher\Channels\Services\Youtube;

use Google\Client;
use Google\Service\YouTube;
use App\Syllaby\Metadata\Metadata;
use Illuminate\Support\Collection;
use Google\Service\YouTube\VideoCategory;
use App\Syllaby\Publisher\Channels\DTOs\YoutubeCategoryData;

class MetadataService
{
    protected Client $client;
    protected YouTube $youtube;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setDeveloperKey(config('services.youtube.developer_key'));

        $this->youtube = new YouTube($this->client);
    }

    public function categories(): array
    {
        if ($categories = $this->fetch()) {
            return array_map(function ($category) {
                return new YoutubeCategoryData($category['id'], $category['title']);
            }, $categories->values);
        }

        $response = $this->youtube->videoCategories->listVideoCategories('snippet', [
            'regionCode' => 'US',
        ]);

        if ($categories = $response->getItems()) {
            $values = $this->parse($categories);
            $this->create($values);
        }

        return $this->categories();
    }

    private function fetch(): ?Metadata
    {
        return Metadata::where('provider', $this->provider())
            ->where('type', 'social-upload')
            ->where('key', 'categories')
            ->first();
    }

    public function parse(array $categories): Collection
    {
        return collect($categories)->filter(
            fn (VideoCategory $category) => $category->getSnippet()->getAssignable()
        )->map(fn (VideoCategory $category) => [
            'id' => $category->getId(),
            'title' => $category->getSnippet()->getTitle(),
        ])->values();
    }

    public function create(Collection $values): void
    {
        Metadata::create([
            'provider' => $this->provider(),
            'type' => 'social-upload',
            'key' => 'categories',
            'values' => $values,
        ]);
    }

    public function provider(): string
    {
        return 'youtube';
    }
}
