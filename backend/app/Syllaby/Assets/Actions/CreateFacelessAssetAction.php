<?php

namespace App\Syllaby\Assets\Actions;

use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Assets\DTOs\AssetCreationData;

class CreateFacelessAssetAction
{
    public function handle(Faceless $faceless, AssetCreationData $data): Asset
    {
        return $faceless->assets()->create([
            'user_id' => $data->user->id,
            'parent_id' => $data->parentId,
            'type' => $data->type,
            'provider' => $data->provider,
            'status' => $data->status,
            'provider_id' => $data->provider_id,
            'genre_id' => $data->genre?->id,
            'description' => $data->description,
            'is_private' => $data->isPrivate,
            'model' => $data->model,
        ], ['order' => $data->order, 'active' => $data->active]);
    }
}
