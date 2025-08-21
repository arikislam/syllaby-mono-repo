<?php

namespace App\Syllaby\Users\Actions;

use Number;
use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Media;
use App\Syllaby\Videos\Video;
use Illuminate\Support\Collection;
use App\Syllaby\Assets\Enums\AssetType;
use Illuminate\Database\Eloquent\Relations\Relation;

class CalculateStorageBreakdownAction
{
    /**
     * Configuration for storage breakdown categories.
     */
    private const array CATEGORIES = [
        'videos' => ['label' => 'Videos', 'types' => []],
        'motion-videos' => ['label' => 'Motion Videos', 'types' => [AssetType::AI_VIDEO, AssetType::FACELESS_BACKGROUND]],
        'uploaded-media' => ['label' => 'Uploaded Media', 'types' => [AssetType::CUSTOM_IMAGE, AssetType::CUSTOM_VIDEO]],
        'stock-media' => ['label' => 'Stock Media', 'types' => [AssetType::STOCK_IMAGE, AssetType::STOCK_VIDEO]],
        'thumbnail' => ['label' => 'Thumbnails', 'types' => [AssetType::THUMBNAIL]],
        'ai-image' => ['label' => 'AI Generated Images', 'types' => [AssetType::AI_IMAGE]],
    ];

    /**
     * Calculate storage breakdown by media types for a user.
     */
    public function handle(User $user): array
    {
        $assets = $this->getAssetsStorage($user);
        $videos = $this->getVideosStorage($user);

        $breakdown = [];

        foreach (self::CATEGORIES as $key => $config) {
            if ($key === 'videos') {
                $totalSize = $videos;
            } else {
                $totalSize = collect($config['types'])->sum(fn (AssetType $type) => $assets->get($type->value, 0));
            }

            $breakdown[$key] = $this->createBreakdownEntry($config['label'], $totalSize);
        }

        return $breakdown;
    }

    /**
     * Create the structured array for a breakdown category.
     */
    private function createBreakdownEntry(string $label, int $size): array
    {
        return [
            'label' => $label,
            'raw' => $size,
            'formatted' => Number::fileSize($size),
        ];
    }

    /**
     * Get total storage for media linked directly to the Video model.
     */
    protected function getVideosStorage(User $user): int
    {
        return (int) Media::query()
            ->where('user_id', $user->id)
            ->where('model_type', Relation::getMorphAlias(Video::class))
            ->sum('size');
    }

    /**
     * Get storage usage grouped by asset type for media linked to the Asset model.
     */
    protected function getAssetsStorage(User $user): Collection
    {
        return Media::query()
            ->where('media.user_id', $user->id)
            ->where('model_type', Relation::getMorphAlias(Asset::class))
            ->join('assets', function ($join) {
                $join->on('media.model_id', '=', 'assets.id')->where('media.model_type', '=', Relation::getMorphAlias(Asset::class));
            })
            ->selectRaw('assets.type, SUM(media.size) as total_size')
            ->groupBy('assets.type')
            ->pluck('total_size', 'type');
    }
}
