<?php

namespace App\System\Traits;

use InvalidArgumentException;

trait HandlesWatermark
{
    public function addSyllabyWatermark(array &$source): array
    {
        if (! isset($source['elements'])) {
            throw new InvalidArgumentException('Unable to add watermark to source without elements');
        }

        $source['elements'][] = $this->syllabyWatermarkElement();

        return $source;
    }

    protected function syllabyWatermarkElement(): array
    {
        return [
            'name' => 'syllaby-watermark',
            'id' => '99e46566-3e7d-4e40-bd15-1cae4039f36a',
            'type' => 'image',
            'x' => '98vw',
            'y' => '98vh',
            'width' => 220,
            'height' => 62,
            'locked' => true,
            'x_anchor' => '100%',
            'y_anchor' => '100%',
            'x_alignment' => '100%',
            'y_alignment' => '100%',
            'source' => 'https://syllaby-assets.sfo3.digitaloceanspaces.com/static/syllaby-logo-white.png',
        ];
    }
}
