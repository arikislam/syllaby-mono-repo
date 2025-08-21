<?php

namespace App\Syllaby\Assets\Filters;

use Illuminate\Support\Arr;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class MediaTypeFilter implements Filter
{
    /**
     * Available filter types
     */
    private array $types = [
        'image' => ['image/jpeg', 'image/webp', 'image/jpg', 'image/png'],
        'audio' => ['audio/mpeg', 'audio/x-m4a', 'audio/m4a', 'audio/mp3'],
        'video' => ['video/webm', 'video/mpeg', 'video/mp4', 'video/quicktime'],
    ];

    /**
     * Apply the filter.
     */
    public function __invoke(Builder $query, $value, string $property): void
    {
        $mimetypes = array_reduce((array) $value, function ($arr, $key) {
            return [...$arr, ...Arr::get($this->types, $key, [])];
        }, []);

        $query->whereIn('mime_type', $mimetypes);
    }
}
