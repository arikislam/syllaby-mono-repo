<?php

namespace Database\Seeders;

use App\Syllaby\Tags\Tag;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Assets\Asset;
use Illuminate\Database\Seeder;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Assets\Enums\AssetStatus;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AssetTableSeeder extends Seeder
{
    public function run(): void
    {
        $this->loadBRollVideos();
        $this->loadAudioSamples();
    }

    private function loadBRollVideos(): void
    {
        if (! $data = $this->loadFile('assets')) {
            return;
        }
        foreach ($data as $item) {
            if (Asset::where('slug', Arr::get($item, 'slug'))->exists()) {
                continue;
            }

            tap($this->persist($item), function (Asset $asset) use ($item) {
                $this->attachMedia($asset, $item);
            });
        }
    }

    private function loadAudioSamples(): void
    {
        if (! $samples = $this->loadFile('stock-music')) {
            return;
        }

        $asset = $this->persist([
            'type' => AssetType::AUDIOS->value,
            'slug' => 'default-stock-audio',
            'name' => 'Default Stock Audio',
        ]);

        $media = $asset->getMedia(AssetType::AUDIOS->value);

        $samples = match (app()->environment()) {
            'local' => Arr::random($samples, min(5, count($samples))),
            'staging', 'development' => Arr::random($samples, min(30, count($samples))),
            default => $samples,
        };

        foreach ($samples as $sample) {
            $item = Arr::only($sample, ['url', 'name', 'slug', 'tag', 'id']);
            $properties = ['is_stock' => true];

            $tag = Arr::get($item, 'tag');
            $category = Tag::firstOrCreate(
                ['user_id' => null, 'slug' => Str::slug($tag)],
                ['name' => $tag]
            );

            if ($media->contains('name', Arr::get($sample, 'name'))) {
                $audio = $media->firstWhere('name', Arr::get($sample, 'name'));
                $category->media()->syncWithoutDetaching([$audio->id]);

                continue;
            }

            $audio = $this->attachMedia($asset, $item, AssetType::AUDIOS->value, $properties);
            $category->media()->syncWithoutDetaching([$audio->id]);
        }
    }

    private function loadFile(string $file)
    {
        $path = storage_path("data/assets/{$file}.json");

        return file_exists($path) ? json_decode(file_get_contents($path), true) : null;
    }

    private function persist(array $item): Asset
    {
        $data = Validator::make($item, [
            'slug' => 'required',
            'name' => 'required',
            'type' => 'required',
        ])->validate();

        $unique = [
            'slug' => Arr::get($data, 'slug'),
        ];

        return Asset::updateOrCreate($unique, [
            'provider' => 'custom',
            'provider_id' => Str::uuid(),
            'name' => Arr::get($data, 'name'),
            'type' => Arr::get($data, 'type'),
            'status' => AssetStatus::SUCCESS->value,
        ]);
    }

    private function attachMedia(Asset $asset, array $item, string $collection = 'default', array $properties = []): ?Media
    {
        if (! $url = Arr::get($item, 'url')) {
            return null;
        }

        $headers = Arr::only(config('media-library.remote.extra_headers'), 'ACL');

        return $asset->addMediaFromUrl($url)->addCustomHeaders($headers)
            ->withAttributes([
                'name' => Arr::get($item, 'name'),
                'uuid' => Arr::get($item, 'id'),
            ])
            ->withCustomProperties($properties)
            ->preservingOriginal()
            ->toMediaCollection($collection);
    }
}
