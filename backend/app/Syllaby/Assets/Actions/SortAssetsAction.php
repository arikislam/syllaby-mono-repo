<?php

namespace App\Syllaby\Assets\Actions;

use App\Syllaby\Assets\VideoAsset;
use Illuminate\Support\Facades\DB;

class SortAssetsAction
{
    /**
     * Sort assets.
     */
    public function handle(VideoAsset $asset, ?VideoAsset $reference = null): int
    {
        return DB::transaction(function () use ($asset, $reference) {
            if (blank($reference)) {
                return $this->moveToFirst($asset);
            }

            if ($reference->order > $asset->order) {
                return $this->moveDown($asset, $reference);
            }

            return $this->moveUp($asset, $reference);
        });
    }

    /**
     * Move the asset to the top of the list.
     */
    protected function moveToFirst(VideoAsset $asset): int
    {
        return VideoAsset::where('model_id', $asset->model_id)
            ->where('model_type', $asset->model_type)
            ->update(['order' => DB::raw("CASE 
                    WHEN `order` = {$asset->order} THEN 0 
                    WHEN `order` < {$asset->order} THEN `order` + 1
                    ELSE `order`
                END"),
            ]);
    }

    /**
     * Move the asset to a lower position.
     */
    protected function moveDown(VideoAsset $asset, VideoAsset $reference): int
    {
        return VideoAsset::where('model_id', $asset->model_id)
            ->where('model_type', $asset->model_type)
            ->update(['order' => DB::raw("CASE
                    WHEN `order` = {$asset->order} THEN {$reference->order}
                    WHEN `order` > {$asset->order} AND `order` <= {$reference->order} THEN `order` - 1
                    ELSE `order`
                END"),
            ]);
    }

    /**
     * Move the asset up.
     */
    protected function moveUp(VideoAsset $asset, VideoAsset $reference): int
    {
        return VideoAsset::where('model_id', $asset->model_id)
            ->where('model_type', $asset->model_type)
            ->update(['order' => DB::raw("CASE
                    WHEN `order` = {$asset->order} THEN ".($reference->order + 1)."
                    WHEN `order` > {$reference->order} AND `order` < {$asset->order} THEN `order` + 1
                    WHEN `order` = {$asset->order} AND id != {$asset->id} THEN ".($reference->order + 1).'
                    ELSE `order`
                END'),
            ]);
    }
}
