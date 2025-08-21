<?php

namespace App\Syllaby\Assets\Actions;

use Arr;
use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Assets\Enums\AssetProvider;

class UploadWatermarkAction
{
    public function __construct(protected UploadMediaAction $action) {}

    public function handle(User $user, array $input): Asset
    {
        $asset = $this->createAsset($user);

        return tap($asset, function (Asset $asset) use ($input) {
            $this->action->handle($asset, Arr::wrap($input['files']));
        });
    }

    private function createAsset(User $user): Asset
    {
        return $user->assets()->create([
            'provider' => AssetProvider::CUSTOM,
            'provider_id' => Str::uuid(),
            'type' => AssetType::WATERMARK,
            'is_private' => true,
            'status' => AssetStatus::SUCCESS,
        ]);
    }
}
