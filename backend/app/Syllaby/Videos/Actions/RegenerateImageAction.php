<?php

namespace App\Syllaby\Videos\Actions;

use Exception;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Characters\Genre;
use App\Syllaby\Trackers\Tracker;
use App\Syllaby\Characters\Character;
use Illuminate\Support\Facades\Cache;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Videos\Enums\Dimension;
use App\Syllaby\Assets\DTOs\AssetCreationData;
use App\Syllaby\Videos\Contracts\ImageModerator;
use App\Syllaby\Assets\Actions\CreateFacelessAssetAction;
use App\Syllaby\Generators\Vendors\Images\Factory;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use App\Syllaby\Assets\Actions\TransloadMediaAction;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Syllaby\Credits\Actions\ChargeImageGenerationAction;
use App\Syllaby\Generators\Prompts\RephraseGenreImagePrompt;

class RegenerateImageAction
{
    public function __construct(
        protected CreateFacelessAssetAction $action,
        protected ImageModerator $moderator,
        protected Factory $factory,
    ) {}

    /**
     * Attempts to regenerate an image based on a previous prompt.
     */
    public function handle(Faceless $faceless, User $user, array $input, Tracker $tracker): Asset
    {
        $index = Arr::get($input, 'index');
        $description = Arr::get($input, 'description');

        $provider = $this->provider($faceless);
        $description = $this->rephrase($faceless, $index, $description);

        $payload = match ($provider) {
            'replicate' => $this->replicate($faceless, $description),
            'syllaby' => $this->syllaby($faceless->genre, $faceless, $description),
        };

        if (! $response = $this->factory->for('replicate')->image($payload, $description)) {
            throw new Exception('Unable to generate image');
        }

        if ($this->moderator->inspect($response->url)->isNSFW()) {
            throw new Exception('Your prompt generated an inappropriate image. Please try a different prompt');
        }

        $data = AssetCreationData::forAiImage($faceless, $response, $index)->success();
        $asset = $this->action->handle($faceless, $data);

        $media = app(TransloadMediaAction::class)->handle($asset, $response->url);

        return tap($asset, fn () => $this->charge($media, $user, $tracker));
    }

    /**
     * Retrieves the provider for the given faceless video.
     */
    private function provider(Faceless $faceless): string
    {
        return blank($faceless->character) ? 'replicate' : 'syllaby';
    }

    /**
     * Generates a new image description for the given image position.
     */
    private function rephrase(Faceless $faceless, int $index, ?string $description = null, int $retry = 1): string
    {
        if ($retry > 3) {
            return Arr::get($this->images($faceless), "{$index}.description");
        }

        if (blank($description)) {
            $images = $this->images($faceless);
            $prompt = RephraseGenreImagePrompt::build($faceless, $images, $index);

            return $this->rephrase($faceless, $index, Chat::driver('claude')->send($prompt)->text, $retry + 1);
        }

        return tap($description, fn () => Cache::forget($this->key($faceless)));
    }

    /**
     * Builds the payload for the Replicate provider.
     */
    private function replicate(Faceless $faceless, string $prompt): array
    {
        /** @var Genre $genre */
        $genre = $faceless->genre ?? Genre::where('slug', 'hyper-realism')->first();

        $payload = $genre->build($prompt, Dimension::fromAspectRatio($faceless->options->aspect_ratio));

        Arr::set($payload, 'input.guidance_scale', 2.5);

        return $payload;
    }

    /**
     * Builds the payload for the Syllaby provider.
     */
    private function syllaby(Genre $genre, Faceless $faceless, string $prompt): array
    {
        /** @var Character $character */
        $character = $faceless->character;

        $data = $genre->build("{$character->trigger} {$prompt}", Dimension::fromAspectRatio($faceless->options->aspect_ratio));

        if ($character->description) {
            Arr::set($data, 'input.prompt', $prompt);
        }

        return array_merge($data, ['model' => $character->model]);
    }

    private function charge(Media $media, User $user, Tracker $tracker): void
    {
        try {
            Cache::lock("tracker:{$tracker->name}:{$tracker->id}", 15)->block(10, function () use ($user, $tracker, $media) {
                $tracker->refresh();

                if ($tracker->count < $tracker->limit) {
                    $tracker->increment('count');

                    return;
                }

                app(ChargeImageGenerationAction::class)->handle($user, $media, 1);
            });
        } catch (LockTimeoutException) {
            throw new Exception('Unable to process request due to high concurrency. Please try again');
        }
    }

    /**
     * Retrieves the images for the given faceless video.
     */
    private function images(Faceless $faceless): array
    {
        return Cache::remember($this->key($faceless), 30, function () use ($faceless) {
            return $faceless->assets()->where('type', AssetType::AI_IMAGE)->wherePivot('active', true)
                ->pluck('assets.description', 'assets.id')
                ->map(fn ($description, $id) => ['id' => $id, 'description' => $description])
                ->values()->all();
        });
    }

    /**
     * Builds the cache key for the given faceless video.
     */
    private function key(Faceless $faceless): string
    {
        return "faceless:{$faceless->id}:images";
    }
}
