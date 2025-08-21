<?php

namespace App\Syllaby\Animation\Actions;

use Arr;
use Number;
use Illuminate\Support\Str;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Collection;
use App\Syllaby\Assets\Enums\AssetProvider;
use App\Syllaby\Animation\Enums\MinimaxStatus;
use App\Syllaby\Assets\DTOs\AssetCreationData;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use Illuminate\Validation\ValidationException;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Animation\Jobs\CreateAnimationJob;
use App\Syllaby\Assets\Actions\CreateFacelessAssetAction;
use App\Syllaby\Animation\DTOs\AnimationGenerationResponse;

readonly class BulkCreateAnimationsAction
{
    public function __construct(private CreateFacelessAssetAction $asset, private CreditService $credit) {}

    public function handle(Faceless $faceless, array $animations): Collection
    {
        $assets = Asset::where('user_id', $faceless->user_id)
            ->whereIn('id', collect($animations)->pluck('id'))
            ->with('media')
            ->get()
            ->keyBy('id');

        $this->validateAssets($animations, $assets);

        $results = collect();

        $indices = collect($animations)->pluck('index')->unique();

        $faceless->assets()->whereIn('video_assets.order', $indices)->update(['active' => false]);

        foreach ($animations as $animation) {
            /** @var Asset $reference */
            $reference = $assets->get($animation['id']);

            $response = $this->getDummyResponse($animation, $reference);

            $data = AssetCreationData::forAiVideo($faceless, $reference, $response, $animation['index']);

            $asset = $this->asset->handle($faceless, $data);

            $results->push($asset);

            $this->credit->setUser($faceless->user)->decrement(CreditEventEnum::IMAGE_ANIMATED, $faceless);

            CreateAnimationJob::dispatch($asset, $faceless, $reference->getFirstMediaUrl(), Arr::get($animation, 'prompt', $reference->description));
        }

        return $results;
    }

    private function validateAssets(array $animations, Collection $assets): void
    {
        foreach ($animations as $index => $animation) {
            /** @var Asset $asset */
            $asset = $assets->get($animation['id']);
            if (! $asset || ! Str::startsWith($asset->getFirstMedia()->mime_type, 'image/')) {
                throw ValidationException::withMessages([Number::ordinal($index).' asset is invalid or not an image.']);
            }
        }
    }

    private function getDummyResponse(mixed $animation, Asset $reference): AnimationGenerationResponse
    {
        return new AnimationGenerationResponse(
            id: sprintf('pending-%s', Str::uuid()),
            provider: AssetProvider::MINIMAX->value,
            model: 'video-01',
            description: Arr::get($animation, 'prompt', $reference->description),
            status: MinimaxStatus::SUCCESS
        );
    }
}
