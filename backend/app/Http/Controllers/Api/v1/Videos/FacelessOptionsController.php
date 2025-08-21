<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Illuminate\Support\Arr;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Characters\Genre;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use App\Http\Resources\GenreResource;
use App\Syllaby\Videos\Enums\Overlay;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Videos\Enums\FactGenre;
use Illuminate\Support\Facades\Storage;
use App\Syllaby\Videos\Enums\Transition;
use App\Syllaby\Videos\Enums\CaptionEffect;
use App\Syllaby\Videos\Vendors\Faceless\Builder\FontPresets;

class FacelessOptionsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display list of font presets.
     */
    public function fonts(): JsonResponse
    {
        $presets = collect(FontPresets::all())->map(fn (array $font, string $slug) => [
            'name' => Arr::get($font, 'name'),
            'color' => Arr::get($font, 'color'),
            'slug' => $slug,
            'font_family' => Arr::get($font, 'font_family'),
            'preview' => Storage::disk('assets')->url("faceless/fonts/{$slug}.webp"),
        ])->values();

        return $this->respondWithArray($presets->toArray());
    }

    /**
     * Display list of all supported genres.
     */
    public function genres(): JsonResponse
    {
        $genres = Genre::active()->get();

        return $this->respondWithResource(GenreResource::collection($genres));
    }

    /**
     * Display list of all supported facts.
     */
    public function facts(): JsonResponse
    {
        $facts = collect(FactGenre::all())->map(fn (string $name, string $slug) => [
            'name' => $name,
            'slug' => $slug,
            'preview' => Storage::disk('assets')->url("faceless/facts/{$slug}.webp"),
        ])->values();

        return $this->respondWithArray($facts->toArray());
    }

    /**
     * Display list of all supported transitions.
     */
    public function transitions(): JsonResponse
    {
        $transitions = collect(Transition::all())->map(fn (string $name, string $slug) => [
            'name' => $name,
            'slug' => $slug,
            'source' => Transition::from($slug)->getConfig(),
            'preview' => Storage::disk('assets')->url("faceless/transitions/{$slug}/preview.gif"),
            'thumbnail' => Storage::disk('assets')->url("faceless/transitions/{$slug}/thumbnail.webp"),
        ])->values();

        return $this->respondWithArray($transitions->toArray());
    }

    /**
     * Display a list of all available video backgrounds.
     */
    public function backgrounds(): JsonResponse
    {
        $assets = Asset::whereIn('type', [AssetType::FACELESS_BACKGROUND->value])->with('media')->get();

        return $this->respondWithResource(AssetResource::collection($assets));
    }

    /**
     * Display a list of available caption effects
     */
    public function effects(): JsonResponse
    {
        $effects = collect(CaptionEffect::all())->map(fn (string $name, string $slug) => [
            'name' => $name,
            'slug' => $slug,
        ])->values();

        return $this->respondWithArray($effects->toArray());
    }

    /**
     * Display a list of available overlays
     */
    public function overlays(): JsonResponse
    {
        $overlays = collect(Overlay::cases())->map(fn (Overlay $overlay) => [
            'name' => $overlay->toString(),
            'slug' => $overlay->value,
            'source' => [
                'elements' => [$overlay->getConfig()],
            ],
        ])->values();

        return $this->respondWithArray($overlays->toArray());
    }
}
