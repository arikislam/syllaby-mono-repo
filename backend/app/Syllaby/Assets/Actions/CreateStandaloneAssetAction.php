<?php

namespace App\Syllaby\Assets\Actions;

use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\DTOs\AssetCreationData;

class CreateStandaloneAssetAction
{
    public function handle(AssetCreationData $data): Asset
    {
        return Asset::create([
            'user_id' => $data->user->id,
            'parent_id' => $data->parentId,
            'type' => $data->type,
            'provider' => $data->provider,
            'status' => $data->status,
            'provider_id' => $data->provider_id,
            'genre_id' => $data->genre?->id,
            'name' => $data->name,
            'description' => $data->description,
            'is_private' => $data->isPrivate,
            'model' => $data->model,
        ]);
    }
}