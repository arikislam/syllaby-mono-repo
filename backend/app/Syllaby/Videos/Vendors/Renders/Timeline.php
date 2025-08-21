<?php

namespace App\Syllaby\Videos\Vendors\Renders;

use Illuminate\Support\Arr;
use App\Syllaby\Assets\Media;

class Timeline
{
    /**
     * Create a new Timeline instance.
     */
    public function __construct(protected array $elements) {}

    /**
     * Create a new named Timeline instance.
     */
    public static function from(array $elements): self
    {
        return new self($elements);
    }

    /**
     * Removes all elements with a reference to the given media.
     */
    public function detach(Media $media): array
    {
        $filter = function ($elements) use (&$filter, $media) {
            return collect($elements)->map(function ($element) use ($filter) {
                if (Arr::has($element, 'elements')) {
                    $element['elements'] = $filter(Arr::get($element, 'elements'));
                }

                return $element;
            })->filter(fn ($element) => match (Arr::get($element, 'type')) {
                'video', 'image', 'audio' => Arr::get($element, 'source') !== $media->getFullUrl(),
                default => true
            })->values()->toArray();
        };

        return $filter($this->elements);
    }
}
