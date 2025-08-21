<?php

namespace App\Syllaby\Assets\Policies;

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\VideoAsset;

class AssetPolicy
{
    /**
     * Determine if the user can view the asset.
     */
    public function view(User $user, Asset $asset): bool
    {
        return $user->owns($asset);
    }

    /**
     * Determine if the user can update the asset.
     */
    public function update(User $user, Asset $asset): bool
    {
        return $user->owns($asset);
    }

    /**
     * Determine if the user can update the asset.
     */
    public function sort(User $user, VideoAsset $asset, ?VideoAsset $reference = null): bool
    {
        if (blank($reference)) {
            return $user->owns($asset->model);
        }

        return $user->owns($asset->model) && $user->owns($reference->model);
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->owns($asset);
    }
}
