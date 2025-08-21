<?php

namespace App\Syllaby\Assets\Actions;

use Exception;
use App\Syllaby\Tags\Tag;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Assets\Enums\AssetProvider;

class UploadUserAudioAction
{
    public function __construct(protected UploadMediaAction $upload) {}

    /**
     * Uploads the given file and associates it to the current model.
     *
     * @throws Exception
     */
    public function handle(array $files, User $user): array
    {
        $asset = $this->resolveAssetCollection($user);
        $tag = $this->resolveUploadAudioTag($user);

        $properties = ['is_stock' => false];
        $media = $this->upload->handle($asset, $files, AssetType::AUDIOS->value, $properties);

        return tap($media, function ($media) use ($tag) {
            $tag->media()->attach(Arr::pluck($media, 'id'));
        });
    }

    /**
     * Retrieves the audio asset collection for the given user.
     */
    protected function resolveAssetCollection(User $user): Asset
    {
        $name = "{$user->name} Audio Collection";

        return Asset::firstOrCreate(
            ['user_id' => $user->id, 'type' => AssetType::AUDIOS->value],
            ['name' => $name, 'slug' => Str::slug($name), 'provider' => AssetProvider::CUSTOM, 'status' => AssetStatus::SUCCESS]
        );
    }

    /**
     * Retrieves the upload audio tag for the given user.
     */
    protected function resolveUploadAudioTag(User $user): Tag
    {
        return Tag::firstOrCreate(
            ['user_id' => $user->id, 'slug' => "user-{$user->id}-audio-uploads"],
            ['name' => 'Audio Uploads']
        );
    }
}
