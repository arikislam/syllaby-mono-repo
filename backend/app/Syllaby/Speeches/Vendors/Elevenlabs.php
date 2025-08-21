<?php

namespace App\Syllaby\Speeches\Vendors;

use Throwable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Speeches\Speech;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Speeches\Enums\SpeechStatus;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Speeches\Enums\SpeechProvider;
use App\Syllaby\Credits\Services\CreditService;
use Illuminate\Http\Client\PendingRequest as Http;
use App\Syllaby\Speeches\Contracts\SpeakerContract;

class Elevenlabs implements SpeakerContract
{
    const int AVERAGE_WORDS_PER_MINUTE = 142;

    public function __construct(protected Http $http) {}

    /**
     * Generate a speech for the given real clone.
     */
    public function generate(Speech $speech, RealClone $clone): Speech
    {
        $response = $this->http->timeout(180)->post("/text-to-speech/{$clone->voice->provider_id}", [
            'text' => $clone->script,
            'model_id' => 'eleven_turbo_v2_5',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
            ],
        ]);

        if ($response->failed()) {
            $response->throw(fn () => Log::error('Error generating speech {id} from Elevenlabs.', [
                'id' => $speech->id,
            ]));
        }

        $this->addAudioFile($speech, $response);

        return tap($speech)->update([
            'synced_at' => now(),
            'status' => SpeechStatus::COMPLETED,
        ]);
    }

    /**
     * Get all available provider voices.
     */
    public function voices(array $allowed): void
    {
        $response = $this->http->withQueryParameters(['show_legacy' => true])->get('/voices');

        if ($response->failed()) {
            $response->throw(fn () => Log::error('Error while fetching ElevenLabs voices.'));
        }

        collect($response->json('voices'))->filter(function ($voice) use ($allowed) {
            $id = Arr::get($voice, 'voice_id');
            $env = Arr::get($voice, 'labels.env');

            return Arr::has($allowed, $id) || $env === app()->environment();
        })->map(function ($voice) use ($allowed) {
            $id = Arr::get($voice, 'voice_id');

            if (Arr::has($allowed, $id)) {
                return array_merge($voice, ['name' => Arr::get($allowed, "{$id}.name")]);
            }

            return $voice;
        })->each(fn ($voice) => $this->persistVoice($voice, $allowed));
    }

    /**
     * Calculate the amount of credits to be charged.
     */
    public function credits(string $text): int
    {
        $unit = config('credit-engine.audio.elevenlabs');
        $chunks = mb_str_split($text, 1000, 'UTF-8');

        return ceil($unit * count($chunks)) ?? $unit;
    }

    /**
     * Calculate and charge the user credits.
     */
    public function charge(Speech $speech, string $text): void
    {
        (new CreditService($speech->user))->decrement(
            type: CreditEventEnum::TEXT_TO_SPEECH_GENERATED,
            creditable: $speech,
            amount: $this->credits($text),
            label: Str::limit($text, CreditHistory::TRUNCATED_LENGTH)
        );
    }

    /**
     * Saves in storage the given voice details.
     */
    private function persistVoice(array $voice, array $allowed): void
    {
        $lookup = [
            'provider_id' => Arr::get($voice, 'voice_id'),
            'provider' => SpeechProvider::ELEVENLABS->value,
        ];

        $attributes = [
            'is_active' => true,
            'name' => Arr::get($voice, 'name'),
            'gender' => Arr::get($voice, 'labels.gender'),
            'accent' => Arr::get($voice, 'labels.accent'),
            'user_id' => Arr::get($voice, 'labels.user_id'),
            'language' => Arr::get($voice, 'fine_tuning.language'),
            'preview_url' => Arr::get($voice, 'preview_url'),
            'metadata' => Arr::only($voice, ['labels', 'settings']),
            'words_per_minute' => Arr::get($allowed, sprintf('%s.words_per_minute', Arr::get($voice, 'voice_id')), self::AVERAGE_WORDS_PER_MINUTE),
            'type' => $type = Arr::has($voice, 'labels.user_id') ? Voice::REAL_CLONE : Voice::STANDARD,
            'order' => $type === Voice::REAL_CLONE ? 0 : Arr::get($allowed, sprintf('%s.order', Arr::get($voice, 'voice_id')), 0),
        ];

        Voice::updateOrCreate($lookup, $attributes);
    }

    /**
     * Creates the speech audio file in the cloud.
     */
    private function addAudioFile(Speech $speech, Response $response): void
    {
        try {
            $filename = sprintf('speech-%s-%s', $speech->id, $speech->voice_id);

            $speech->addMediaFromStream($response->toPsrResponse()->getBody())
                ->addCustomHeaders(['ACL' => 'public-read'])
                ->usingFileName("{$filename}.mp3")
                ->usingName($filename)
                ->toMediaCollection('script');
        } catch (Throwable $exception) {
            Log::error('Unable to upload speech {id} audio file', [
                'id' => $speech->id,
                'reason' => $exception->getMessage(),
            ]);
        }
    }
}
