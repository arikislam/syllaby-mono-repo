<?php

namespace App\Syllaby\Characters\Actions;

use Arr;
use Log;
use Exception;
use Illuminate\Support\Sleep;
use App\Syllaby\Characters\Genre;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Characters\Character;
use App\Syllaby\Characters\Enums\CharacterStatus;
use App\Syllaby\Assets\Actions\TransloadMediaAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Syllaby\Generators\DTOs\ImageGeneratorResponse;

class CreateCharacterPreviewAction
{
    const int MAX_ATTEMPTS = 90;

    const int SLEEP_TIME = 1;

    public function __construct(protected TransloadMediaAction $transload)
    {
    }

    public function handle(Character $character, array $input): Character
    {
        $media = Media::find($input['image_id']);

        $genre = Genre::where('id', $input['genre_id'])->first();

        if (blank($genre) || blank($media) || blank($genre->prompt)) {
            throw new Exception("The reference media or style is not valid or does not exist.");
        }

        $response = Http::replicate()->post('/models/black-forest-labs/flux-kontext-pro/predictions', [
            'input' => [
                'prompt' => $genre->prompt,
                'input_image' => $media->getFullUrl(),
                'output_format' => 'jpg',
            ],
        ]);

        if ($response->failed()) {
            $character->update(['status' => CharacterStatus::PREVIEW_FAILED]);
            Log::error('Failed to generate preview', ['character' => $character->id, 'response' => $response->json()]);
            throw new Exception('Failed to generate character preview.');
        }

        if (blank($response = $this->waitForResponse($response->json('id')))) {
            $character->update(['status' => CharacterStatus::PREVIEW_FAILED]);
            throw new Exception('Failed to generate character preview.');
        }

        $output = ImageGeneratorResponse::fromReplicate($response->json());

        $this->transload->handle($character, $output->url, 'sandbox', [
            'reference_media_id' => $media->id,
            'genre_id' => Arr::get($input, 'genre_id'),
        ]);

        return tap($character)->update(['status' => CharacterStatus::PREVIEW_READY]);
    }

    public function waitForResponse(string $id, int $attempts = self::MAX_ATTEMPTS): ?Response
    {
        if ($attempts <= 0) {
            return null;
        }

        Sleep::for(self::SLEEP_TIME)->seconds();

        $response = Http::replicate()->get("/predictions/{$id}");

        if (in_array($response->json('status'), ['starting', 'processing'])) {
            return $this->waitForResponse($id, $attempts - 1);
        }

        if (blank($response) || $response->json('status') !== 'succeeded') {
            return null;
        }

        return $response;
    }
}
