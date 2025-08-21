<?php

namespace App\Syllaby\Videos\Vendors\Faceless\Builder;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FontPresets
{
    /**
     * All available font presets.
     */
    private static array $presets = [
        'none' => [
            'name' => 'None',
            'color' => null,
            'font_family' => null,
        ],
        'lively' => [
            'name' => 'Lively',
            'color' => '#87faff',
            'font_family' => 'Luckiest Guy',
        ],
        'dynamo' => [
            'name' => 'Dynamo',
            'color' => '#ffb700',
            'font_family' => 'Passion One',
        ],
        'bubbly' => [
            'name' => 'Bubbly',
            'color' => '#ffc6ff',
            'font_family' => 'Bubblegum Sans',
        ],
        'vivid' => [
            'name' => 'Vivid',
            'color' => '#ffea00',
            'font_family' => 'Montserrat',
        ],
        'fiesta' => [
            'name' => 'Fiesta',
            'color' => '#ff47f0',
            'font_family' => 'Galindo',
        ],
        'jolly' => [
            'name' => 'Jolly',
            'color' => '#6ff787',
            'font_family' => 'Luckiest Guy',
        ],
        'valour' => [
            'name' => 'Valour',
            'color' => '#a3ff00',
            'font_family' => 'Righteous',
        ],
        'gummy' => [
            'name' => 'Gummy',
            'color' => '#ebff2d',
            'font_family' => 'Chewy',
        ],
        'bulky' => [
            'name' => 'Bulky',
            'color' => '#00ffc9',
            'font_family' => 'Bowlby One',
        ],
        'kaboom' => [
            'name' => 'Kaboom',
            'color' => '#ffffff',
            'font_family' => 'Bangers',
        ],
        'sunny' => [
            'name' => 'Sunny',
            'color' => '#f2f27a',
            'font_family' => 'Days One',
        ],
        'whimsy' => [
            'name' => 'Whimsy',
            'color' => '#07ff00',
            'font_family' => 'Luckiest Guy',
        ],
        'alloy' => [
            'name' => 'Alloy',
            'color' => '#ff0000',
            'font_family' => 'Metal Mania',
        ],
        'grin' => [
            'name' => 'Grin',
            'color' => '#ffd700',
            'font_family' => 'Happy Monkey',
        ],
        'creepster' => [
            'name' => 'Creepster',
            'color' => '#5d8a32',
            'font_family' => 'Creepster',
        ],
        'pixel' => [
            'name' => 'Pixel',
            'color' => '#50c878',
            'font_family' => 'DotGothic16',
        ],
        'elegant' => [
            'name' => 'Elegant',
            'color' => '#daa520',
            'font_family' => 'Yatra One',
        ],
        'bliss' => [
            'name' => 'Bubbly Bliss',
            'color' => '#FF6B6B',
            'font_family' => 'Fredoka One',
        ],
        'jumbo' => [
            'name' => 'Jolly Jumbo',
            'color' => '#FFB400',
            'font_family' => 'Baloo Bhai 2',
        ],
        'electric' => [
            'name' => 'Electric Edge',
            'color' => '#FF3D7F',
            'font_family' => 'Knewave',
        ],
        'groove' => [
            'name' => 'Retro Groove',
            'color' => '#FF9F1C',
            'font_family' => 'Boogaloo',
        ],
        'scribbler' => [
            'name' => 'Wild Scribbler',
            'color' => '#333333',
            'font_family' => 'Permanent Marker',
        ],
        'rebel' => [
            'name' => 'Rough Rebel',
            'color' => '#7C3E3E',
            'font_family' => 'Rock Salt',
        ],
        'charming' => [
            'name' => 'Charming Curve',
            'color' => '#A04BAE',
            'font_family' => 'Cherry Swash',
        ],
        'harmony' => [
            'name' => 'Happy Harmony',
            'color' => '#5AC18E',
            'font_family' => 'Concert One',
        ],
        'pixel-art' => [
            'name' => 'Pixel Pioneer',
            'color' => '#00A3E0',
            'font_family' => 'Press Start 2P',
        ],
        'charm' => [
            'name' => 'Casual Charm',
            'color' => '#FF9A8B',
            'font_family' => 'Shadows Into Light',
        ],
        'aura' => [
            'name' => 'Aura',
            'color' => '#A8DADC',
            'font_family' => 'Spectral',
        ],
        'lumen' => [
            'name' => 'Lumen',
            'color' => '#FFE8A1',
            'font_family' => 'Manrope',
        ],
        'halo' => [
            'name' => 'Halo',
            'color' => '#F0F8E2',
            'font_family' => 'Mulish',
        ],
        'axis' => [
            'name' => 'Axis',
            'color' => '#BFD8BD',
            'font_family' => 'Urbanist',
        ],
        'verdue' => [
            'name' => 'Verdue',
            'color' => '#F0D9B5',
            'font_family' => 'Cardo',
        ],
        'nova' => [
            'name' => 'Nova',
            'color' => '#CED4DA',
            'font_family' => 'Public Sans',
        ],
        'pulse' => [
            'name' => 'Pulse',
            'color' => '#3DA9FC',
            'font_family' => 'Inter',
        ],
        'grid' => [
            'name' => 'Grid',
            'color' => '#ADCBE3',
            'font_family' => 'Work Sans',
        ],
        'zen' => [
            'name' => 'Zen',
            'color' => '#C5B4E3',
            'font_family' => 'DM Sans',
        ],
        'epoch' => [
            'name' => 'Epoch',
            'color' => '#A9D6C2',
            'font_family' => 'Epliogue',
        ],
    ];

    /**
     * Gets the preset names only.
     */
    public static function values(): array
    {
        return array_keys(static::$presets);
    }

    /**
     * Get all presets available.
     */
    public static function all(): array
    {
        return static::$presets;
    }

    /**
     * Get a specific preset and binds new values.
     */
    public static function get(string $name, callable $callback): array
    {
        return match ($name) {
            'lively' => static::lively($callback),
            'dynamo' => static::dynamo($callback),
            'bubbly' => static::bubbly($callback),
            'vivid' => static::vivid($callback),
            'fiesta' => static::fiesta($callback),
            'jolly' => static::jolly($callback),
            'valour' => static::valour($callback),
            'gummy' => static::gummy($callback),
            'bulky' => static::bulky($callback),
            'kaboom' => static::kaboom($callback),
            'sunny' => static::sunny($callback),
            'whimsy' => static::whimsy($callback),
            'alloy' => static::alloy($callback),
            'grin' => static::grin($callback),
            'creepster' => static::creepster($callback),
            'pixel' => static::pixel($callback),
            'elegant' => static::elegant($callback),
            'bliss' => static::bliss($callback),
            'jumbo' => static::jumbo($callback),
            'electric' => static::electric($callback),
            'groove' => static::groove($callback),
            'scribbler' => static::scribbler($callback),
            'rebel' => static::rebel($callback),
            'charming' => static::charming($callback),
            'harmony' => static::harmony($callback),
            'pixel-art' => static::pixelArt($callback),
            'charm' => static::charm($callback),
            'aura' => static::aura($callback),
            'lumen' => static::lumen($callback),
            'halo' => static::halo($callback),
            'axis' => static::axis($callback),
            'verdue' => static::verdue($callback),
            'nova' => static::nova($callback),
            'pulse' => static::pulse($callback),
            'grid' => static::grid($callback),
            'zen' => static::zen($callback),
            'epoch' => static::epoch($callback),
            default => static::noto($callback),
        };
    }

    /**
     * Lively font preset.
     */
    private static function lively(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(255,255,255,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Luckiest Guy',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'lively.color'),
        ], $callback);
    }

    /**
     * Dynamo font preset.
     */
    private static function dynamo(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => '#333333',
            'stroke_color' => '#000000',
            'stroke_width' => '1 vmin',
            'font_family' => 'Passion One',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'dynamo.color'),
        ], $callback);
    }

    /**
     * Bubbly font preset.
     */
    private static function bubbly(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(14,34,57,1)',
            'stroke_color' => '#000000',
            'stroke_width' => '1 vmin',
            'font_family' => 'Bubblegum Sans',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'bubbly.color'),
        ], $callback);
    }

    /**
     * Vivid font preset.
     */
    private static function vivid(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(255,255,255,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Montserrat',
            'font_weight' => '800',
            'font_style' => 'italic',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'vivid.color'),
        ], $callback);
    }

    /**
     * Fiesta font preset.
     */
    private static function fiesta(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(255,255,255,1)',
            'stroke_color' => '#000000',
            'stroke_width' => '1 vmin',
            'font_family' => 'Galindo',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'fiesta.color'),
        ], $callback);
    }

    /**
     * Jolly font preset.
     */
    private static function jolly(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => '#ffffff',
            'stroke_color' => '#000000',
            'stroke_width' => '1 vmin',
            'font_family' => 'Luckiest Guy',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 12,
            'transcript_color' => Arr::get(static::$presets, 'jolly.color'),
        ], $callback);
    }

    /**
     * Valour font preset.
     */
    private static function valour(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => '#ffffff',
            'stroke_color' => '#000000',
            'stroke_width' => '1 vmin',
            'font_family' => 'Righteous',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 20,
            'transcript_color' => Arr::get(static::$presets, 'valour.color'),
        ], $callback);
    }

    /**
     * Gummy font preset.
     */
    private static function gummy(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => '#ffffff',
            'stroke_color' => '#000000',
            'stroke_width' => '1 vmin',
            'font_family' => 'Chewy',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 12,
            'transcript_color' => Arr::get(static::$presets, 'gummy.color'),
        ], $callback);
    }

    /**
     * Bulky font preset.
     */
    private static function bulky(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => '#ffffff',
            'stroke_color' => '#000000',
            'stroke_width' => '1 vmin',
            'font_family' => 'Bowlby One',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 4,
            'transcript_color' => Arr::get(static::$presets, 'bulky.color'),
        ], $callback);
    }

    /**
     * Kaboom font preset.
     */
    private static function kaboom(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => '#ffffff',
            'stroke_color' => '#000000',
            'stroke_width' => '1 vmin',
            'font_family' => 'Bangers',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 12,
            'transcript_color' => Arr::get(static::$presets, 'kaboom.color'),
        ], $callback);
    }

    /**
     * Sunny font preset.
     */
    private static function sunny(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(255,255,255,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Days One',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 8,
            'transcript_color' => Arr::get(static::$presets, 'sunny.color'),
        ], $callback);
    }

    /**
     * Whimsy font preset.
     */
    private static function whimsy(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(255,255,255,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Luckiest Guy',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'slide',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 20,
            'transcript_color' => Arr::get(static::$presets, 'whimsy.color'),
        ], $callback);
    }

    /**
     * Alloy font preset.
     */
    private static function alloy(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(255,255,255,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'metal mania',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'alloy.color'),
        ], $callback);
    }

    /**
     * Grin font preset.
     */
    private static function grin(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => '#ffffff',
            'stroke_color' => '#000000',
            'stroke_width' => '1 vmin',
            'font_family' => 'Happy Monkey',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'grin.color'),
        ], $callback);
    }

    /**
     * Creepster font preset.
     */
    private static function creepster(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(255,255,255,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Creepster',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'creepster.color'),
        ], $callback);
    }

    /**
     * Pixel font preset.
     */
    private static function pixel(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(255,255,255,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'DotGothic16',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'pixel.color'),
        ], $callback);
    }

    /**
     * Elegant font preset.
     */
    private static function elegant(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(255,255,255,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Yatra One',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_split' => 'none',
            'transcript_maximum_length' => 6,
            'transcript_color' => Arr::get(static::$presets, 'elegant.color'),
        ], $callback);
    }

    /**
     * Elegant font preset.
     */
    private static function noto(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(255,255,255,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Noto Sans',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_split' => 'none',
            'transcript_maximum_length' => 6,
            'transcript_color' => '#a3ff00',
        ], $callback);
    }

    /**
     * Bliss font preset.
     */
    private static function bliss(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'width' => '90%',
            'height' => '95%',
            'x_alignment' => '50%',
            'y_alignment' => '50%',
            'fill_color' => 'rgba(255,107,107,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Fredoka One',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'bliss.color'),
        ], $callback);
    }

    /**
     * Jumbo font preset.
     */
    private static function jumbo(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'width' => '90%',
            'height' => '95%',
            'x_alignment' => '50%',
            'y_alignment' => '50%',
            'fill_color' => 'rgba(255,180,0,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Baloo Bhai 2',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'jumbo.color'),
        ], $callback);
    }

    /**
     * Electric font preset.
     */
    private static function electric(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'width' => '90%',
            'height' => '95%',
            'x_alignment' => '50%',
            'y_alignment' => '50%',
            'fill_color' => 'rgba(255,61,127,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'knewave',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'electric.color'),
        ], $callback);
    }

    /**
     * Groove font preset.
     */
    private static function groove(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'width' => '90%',
            'height' => '95%',
            'x_alignment' => '50%',
            'y_alignment' => '50%',
            'fill_color' => 'rgba(255,159,28,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'boogaloo',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'groove.color'),
        ], $callback);
    }

    /**
     * Scribbler font preset.
     */
    private static function scribbler(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'width' => '90%',
            'height' => '95%',
            'x_alignment' => '50%',
            'y_alignment' => '50%',
            'fill_color' => 'rgba(51,51,51,1)',
            'font_family' => 'Permanent Marker',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'scribbler.color'),
        ], $callback);
    }

    /**
     * Rebel font preset.
     */
    private static function rebel(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'width' => '90%',
            'height' => '95%',
            'x_alignment' => '50%',
            'y_alignment' => '50%',
            'fill_color' => 'rgba(124,62,62,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Rock Salt',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'rebel.color'),
        ], $callback);
    }

    /**
     * Charming font preset.
     */
    private static function charming(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'width' => '90%',
            'height' => '95%',
            'x_alignment' => '50%',
            'y_alignment' => '50%',
            'fill_color' => 'rgba(160,75,174,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Cherry Swash',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'charming.color'),
        ], $callback);
    }

    /**
     * Harmony font preset.
     */
    private static function harmony(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'width' => '90%',
            'height' => '95%',
            'x_alignment' => '50%',
            'y_alignment' => '50%',
            'fill_color' => 'rgba(90,193,142,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Concert One',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'line_height' => '112%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'harmony.color'),
        ], $callback);
    }

    /**
     * Pixel-art font preset.
     */
    private static function pixelArt(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'width' => '90%',
            'height' => '95%',
            'x_alignment' => '50%',
            'y_alignment' => '50%',
            'fill_color' => 'rgba(0,163,224,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Press Start 2P',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'line_height' => '112%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'pixel-art.color'),
        ], $callback);
    }

    /**
     * Charm font preset.
     */
    private static function charm(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'width' => '90%',
            'height' => '95%',
            'x_alignment' => '50%',
            'y_alignment' => '50%',
            'fill_color' => 'rgba(255,154,139,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Shadows Into Light',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'line_height' => '112%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 1,
            'transcript_color' => Arr::get(static::$presets, 'charm.color'),
        ], $callback);
    }

    private static function aura(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(168,218,220,1)',
            'stroke_color' => 'rgba(0,0,0,1)',
            'stroke_width' => '1 vmin',
            'font_family' => 'Spectral',
            'font_weight' => '600',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 8,
            'transcript_color' => Arr::get(static::$presets, 'aura.color'),
        ], $callback);
    }

    private static function lumen(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(255,232,161,1)',
            'stroke_color' => '#333333',
            'stroke_width' => '1 vmin',
            'font_family' => 'Manrope',
            'font_weight' => '500',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 8,
            'transcript_color' => Arr::get(static::$presets, 'lumen.color'),
        ], $callback);
    }

    private static function halo(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(240,248,226,1)',
            'stroke_color' => '#333333',
            'stroke_width' => '1 vmin',
            'font_family' => 'Mulish',
            'font_weight' => '500',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 8,
            'transcript_color' => Arr::get(static::$presets, 'halo.color'),
        ], $callback);
    }

    private static function axis(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(191,216,189,1)',
            'stroke_color' => '#333333',
            'stroke_width' => '1 vmin',
            'font_family' => 'Urbanist',
            'font_weight' => '500',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 8,
            'transcript_color' => Arr::get(static::$presets, 'axis.color'),
        ], $callback);
    }

    private static function verdue(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(240,217,181,1)',
            'stroke_color' => '#333333',
            'stroke_width' => '1 vmin',
            'font_family' => 'Cardo',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 8,
            'transcript_color' => Arr::get(static::$presets, 'verdue.color'),
        ], $callback);
    }

    private static function nova(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(206,212,218,1)',
            'stroke_color' => '#333333',
            'stroke_width' => '1 vmin',
            'font_family' => 'Public Sans',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 8,
            'transcript_color' => Arr::get(static::$presets, 'nova.color'),
        ], $callback);
    }

    private static function pulse(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(61,169,252,1)',
            'stroke_color' => '#333333',
            'stroke_width' => '1 vmin',
            'font_family' => 'Inter',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 8,
            'transcript_color' => Arr::get(static::$presets, 'pulse.color'),
        ], $callback);
    }

    private static function epoch(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(169,214,194,1)',
            'stroke_color' => '#333333',
            'stroke_width' => '1 vmin',
            'font_family' => 'Epilogue',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 8,
            'transcript_color' => Arr::get(static::$presets, 'epoch.color'),
        ], $callback);
    }

    private static function zen(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(197,180,227,1)',
            'stroke_color' => '#333333',
            'stroke_width' => '1 vmin',
            'font_family' => 'DM Sans',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 8,
            'transcript_color' => Arr::get(static::$presets, 'zen.color'),
        ], $callback);
    }

    private static function grid(callable $callback): array
    {
        return static::bind([
            'id' => Str::uuid()->toString(),
            'type' => 'text',
            'track' => 3,
            'time' => 0,
            'x' => '50%',
            'y' => '50%',
            'width' => '90%',
            'height' => '95%',
            'fill_color' => 'rgba(173,203,227,1)',
            'stroke_color' => '#333333',
            'stroke_width' => '1 vmin',
            'font_family' => 'Work Sans',
            'font_size_maximum' => '8 vmin',
            'letter_spacing' => '32%',
            'transcript_effect' => 'bounce',
            'transcript_placement' => 'animate',
            'transcript_maximum_length' => 8,
            'transcript_color' => Arr::get(static::$presets, 'grid.color'),
        ], $callback);
    }

    /**
     * Allows presents to be configurable.
     */
    private static function bind(array $element, ?callable $callback = null): array
    {
        return array_merge($element, $callback($element));
    }
}
