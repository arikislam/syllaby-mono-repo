<?php

namespace App\Syllaby\Videos\Enums;

use Str;
use App\System\Traits\HasEnumValues;

enum Overlay: string
{
    use HasEnumValues;

    case NONE = 'none';
    case VHS = 'vhs';
    case RAIN = 'rain';
    case GLITCH = 'glitch';
    case DUST = 'dust';
    case SPARKLING_GOLD = 'sparkling-gold';
    case SPARK_EFFECT = 'spark-effect';
    case ABSTRACT_PARTICLES = 'abstract-particles';

    public function toString(): string
    {
        return Str::of($this->value)->replace('-', ' ')->title();
    }

    public function getConfig(?callable $callback = null): array
    {
        $config = match ($this) {
            self::VHS => $this->vhs(),
            self::RAIN => $this->rain(),
            self::GLITCH => $this->glitch(),
            self::DUST => $this->dust(),
            self::SPARKLING_GOLD => $this->sparklingGold(),
            self::SPARK_EFFECT => $this->sparkEffect(),
            self::ABSTRACT_PARTICLES => $this->abstractParticles(),
            default => [],
        };

        return $this->overwrite($config, $callback);
    }

    private function overwrite(array $config, ?callable $callback): array
    {
        if ($callback) {
            $config = array_merge($config, $callback($config));
        }

        return $config;
    }

    private function vhs(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'name' => 'Overlay',
            'type' => 'composition',
            'track' => 3,
            'time' => 0,
            'elements' => [
                [
                    'id' => Str::uuid()->toString(),
                    'type' => 'video',
                    'name' => 'vhs-overlay',
                    'track' => 1,
                    'time' => 0,
                    'duration' => null,
                    'loop' => true,
                    'blend_mode' => 'screen',
                    'source' => 'https://syllaby-assets.sfo3.digitaloceanspaces.com/faceless/overlays/vhs/vhs.mp4',
                ],
                [
                    'id' => Str::uuid()->toString(),
                    'type' => 'shape',
                    'track' => 2,
                    'time' => 0,
                    'width' => '100%',
                    'height' => '100%',
                    'x_anchor' => '50%',
                    'y_anchor' => '50%',
                    'fill_color' => 'rgba(0,0,0,0.1)',
                    'path' => 'M 0 0 L 100 0 L 100 100 L 0 100 L 0 0 Z',
                ],
            ],
        ];
    }

    private function rain(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'name' => 'Overlay',
            'type' => 'composition',
            'track' => 3,
            'time' => 0,
            'elements' => [
                [
                    'id' => Str::uuid()->toString(),
                    'name' => 'rain-overlay',
                    'type' => 'video',
                    'track' => 1,
                    'height' => '100%',
                    'blend_mode' => 'screen',
                    'duration' => null,
                    'loop' => true,
                    'source' => 'https://syllaby-assets.sfo3.digitaloceanspaces.com/faceless/overlays/rain/rain.mp4',
                ],
            ],
        ];
    }

    private function glitch(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'name' => 'Overlay',
            'type' => 'composition',
            'track' => 3,
            'time' => 0,
            'elements' => [
                [
                    'id' => Str::uuid()->toString(),
                    'name' => 'glitch-overlay',
                    'type' => 'video',
                    'track' => 1,
                    'duration' => null,
                    'loop' => true,
                    'blend_mode' => 'screen',
                    'source' => 'https://syllaby-assets.sfo3.digitaloceanspaces.com/faceless/overlays/glitch/glitch.mp4',
                ],
            ],
        ];
    }

    private function dust(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'name' => 'Overlay',
            'type' => 'composition',
            'track' => 3,
            'time' => 0,
            'elements' => [
                [
                    'id' => Str::uuid()->toString(),
                    'type' => 'video',
                    'name' => 'dust-overlay',
                    'track' => 1,
                    'duration' => null,
                    'loop' => true,
                    'height' => '100%',
                    'blend_mode' => 'screen',
                    'source' => 'https://syllaby-assets.sfo3.digitaloceanspaces.com/faceless/overlays/dust/dust.mp4',
                ],
            ],
        ];
    }

    private function sparklingGold(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'name' => 'Overlay',
            'type' => 'composition',
            'track' => 3,
            'time' => 0,
            'elements' => [
                [
                    'id' => Str::uuid()->toString(),
                    'name' => 'sparkling-gold-overlay',
                    'type' => 'video',
                    'track' => 1,
                    'duration' => null,
                    'loop' => true,
                    'blend_mode' => 'screen',
                    'source' => 'https://syllaby-assets.sfo3.digitaloceanspaces.com/faceless/overlays/abstract-particles/abstract-particles.mp4',
                ],
            ],
        ];
    }

    private function sparkEffect(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'name' => 'Overlay',
            'type' => 'composition',
            'track' => 3,
            'time' => 0,
            'elements' => [
                [
                    'id' => Str::uuid()->toString(),
                    'name' => 'spark-effect-overlay',
                    'type' => 'video',
                    'track' => 1,
                    'duration' => null,
                    'loop' => true,
                    'blend_mode' => 'screen',
                    'source' => 'https://syllaby-assets.sfo3.digitaloceanspaces.com/faceless/overlays/spark-effect/spark-effect.mp4',
                ],
            ],
        ];
    }

    private function abstractParticles(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'name' => 'Overlay',
            'type' => 'composition',
            'track' => 3,
            'time' => 0,
            'elements' => [
                [
                    'id' => Str::uuid()->toString(),
                    'name' => 'abstract-particles-overlay',
                    'type' => 'video',
                    'track' => 1,
                    'duration' => null,
                    'loop' => true,
                    'opacity' => '80%',
                    'blend_mode' => 'screen',
                    'source' => 'https://syllaby-assets.sfo3.digitaloceanspaces.com/faceless/overlays/abstract-particles/abstract-particles.mp4',
                ],
            ],
        ];
    }
}
