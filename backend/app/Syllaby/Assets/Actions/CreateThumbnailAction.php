<?php

namespace App\Syllaby\Assets\Actions;

use Exception;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Assets\Asset;
use Illuminate\Support\Collection;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Assets\Enums\AssetProvider;
use App\Syllaby\Generators\DTOs\ChatConfig;
use App\Syllaby\Generators\Prompts\ThumbnailPrompt;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use App\Syllaby\Generators\Contracts\ImageGenerator;
use App\Syllaby\Generators\DTOs\ImageGeneratorResponse;

class CreateThumbnailAction
{
    /**
     * Handle the creation of a thumbnail.
     *
     * @throws Exception
     */
    public function handle(User $user, array $input): Collection
    {
        if (! $prompts = $this->prompts($input)) {
            throw new Exception('Unable to generate image prompts');
        }

        $predictions = collect($prompts)->map(function ($prompt) {
            return $this->generate($prompt);
        });

        if ($predictions->isEmpty()) {
            throw new Exception('Unable to generate thumbnails');
        }

        return $predictions->map(function ($prediction) use ($user) {
            return $this->createAssetFrom($prediction, $user);
        });
    }

    /**
     * Generates the prompts for the thumbnail generation.
     *
     * @throws Exception
     */
    private function prompts(array $input): array
    {
        $chat = Chat::driver('claude');

        $config = match (Chat::getFacadeRoot()->getCurrentDriver()) {
            'gpt' => new ChatConfig(responseFormat: config('openai.json_schemas.thumbnails')),
            default => null,
        };

        $amount = Arr::get($input, 'amount', 3);
        $prompt = ThumbnailPrompt::build(
            Arr::get($input, 'context'),
            $amount,
            Arr::get($input, 'text'),
            Arr::get($input, 'color')
        );

        $response = $chat->send($prompt, $config);

        if (! $response->text || ! json_validate($response->text)) {
            throw new Exception('Unable to generate image prompts');
        }

        $prompts = Arr::get(json_decode($response->text, true), 'prompts');

        if (count($prompts) !== $amount) {
            throw new Exception('Unable to generate image prompts');
        }

        return $prompts;
    }

    /**
     * Generates a thumbnail from a prompt.
     *
     * @throws Exception
     */
    private function generate(string $prompt): ImageGeneratorResponse
    {
        $data = [
            'model' => 'black-forest-labs/flux-schnell',
            'input' => [
                'prompt' => $prompt,
                'go_fast' => true,
                'megapixels' => '1',
                'num_outputs' => 1,
                'aspect_ratio' => '16:9',
                'output_format' => 'jpg',
                'output_quality' => 80,
                'num_inference_steps' => 4,
            ],
        ];

        if (! $prediction = app(ImageGenerator::class)->image($data, $prompt)) {
            throw new Exception('Unable to generate thumbnail');
        }

        return $prediction;
    }

    /**
     * Creates an asset from a prediction.
     *
     * @throws Exception
     */
    private function createAssetFrom(ImageGeneratorResponse $prediction, User $user): Asset
    {
        $asset = Asset::create([
            'is_private' => true,
            'user_id' => $user->id,
            'type' => AssetType::THUMBNAIL,
            'status' => AssetStatus::SUCCESS,
            'provider_id' => $prediction->id,
            'model' => $prediction->model,
            'provider' => AssetProvider::REPLICATE,
            'description' => $prediction->description,
        ]);

        $media = app(TransloadMediaAction::class)->handle($asset, $prediction->url);
        $asset->setRelation('media', [$media]);

        return $asset;
    }
}
