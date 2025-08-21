<?php

namespace App\Syllaby\Videos\Vendors\Faceless\Speech;

use RuntimeException;
use IntlBreakIterator;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Videos\Faceless;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Credits\CreditHistory;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use Illuminate\Http\Client\ConnectionException;
use App\Syllaby\Generators\DTOs\CaptionResponse;
use App\Syllaby\Speeches\Transformers\TextTransformer;

class Elevenlabs
{
    /**
     * Triggers the voiceover generation for the given script.
     *
     * @throws ConnectionException
     */
    public function generate(string $script, array $options = []): ?array
    {
        $voice = Arr::get($options, 'voice_id');
        $language = Arr::get($options, 'language', 'none');

        $model = $this->model($script, $language);

        $script = (new TextTransformer)->format($script, 'elevenlabs');
        $segments = $this->splitScript($script, $language);

        $requests = [];
        $responses = [];

        foreach ($segments as $index => $segment) {
            $response = $this->http()->post("/text-to-speech/{$voice}/stream/with-timestamps", $this->payload([
                'text' => $segment,
                'model_id' => $model,
                'language_code' => $language,
                'apply_text_normalization' => 'off',
                'voice_settings' => $this->settings($options),
                'previous_request_ids' => array_slice($requests, -3),
                'next_text' => Arr::get($segments, $index + 1, null),
                'previous_text' => Arr::get($segments, $index - 1, null),
            ]));

            if ($response->failed()) {
                return null;
            }

            if ($id = $response->header('request-id')) {
                $requests[] = $id;
            }

            $responses[] = $this->process($response);
        }

        $responses = collect($responses);

        $audio = $responses->map(function ($response) {
            return array_values(Arr::pluck($response, 'audio_base64'));
        })->values()->toArray();

        $transcriptions = $responses->map(function ($response) {
            return array_values(array_filter(Arr::pluck($response, 'normalized_alignment')));
        })->values()->toArray();

        return [
            'audio' => $this->decode($audio),
            'captions' => CaptionResponse::fromElevenlabs($transcriptions)->captions,
        ];
    }

    /**
     * Calculate the amount of credits to be charged.
     */
    public function credits(string $script, int $duration): int|float
    {
        if ($duration <= 60) {
            return config('credit-engine.audio.elevenlabs');
        }

        $unit = config('credit-engine.audio.elevenlabs');
        $chunks = mb_str_split($script, 1000, 'UTF-8');

        return ceil($unit * count($chunks)) ?? $unit;
    }

    /**
     * Charge the user for the given voiceover generation.
     */
    public function charge(int $credits, Faceless $faceless, User $user): void
    {
        (new CreditService($user))->decrement(
            type: CreditEventEnum::TEXT_TO_SPEECH_GENERATED,
            creditable: $faceless,
            amount: $credits,
            label: Str::limit($faceless->script, CreditHistory::TRUNCATED_LENGTH)
        );
    }

    /**
     * Get the payload for the given script and language.
     */
    private function payload(array $input): array
    {
        if (Arr::get($input, 'model_id') === 'eleven_turbo_v2_5') {
            return $input;
        }

        $input = Arr::except($input, ['language_code']);

        return array_merge($input, ['apply_text_normalization' => 'on']);
    }

    /**
     * Get the settings to use for the given options.
     */
    private function settings(array $options): array
    {
        return match (Arr::get($options, 'voice_type')) {
            'standard' => [
                'style' => 0.5,
                'stability' => 0.5,
                'similarity_boost' => 0.9,
                'use_speaker_boost' => true,
            ],
            'real-clone' => [
                'style' => 0.4,
                'stability' => 0.5,
                'similarity_boost' => 1.0,
                'use_speaker_boost' => true,
            ],
            default => [
                'style' => 0.15,
                'stability' => 0.38,
                'similarity_boost' => 0.80,
                'use_speaker_boost' => true,
            ],
        };
    }

    /**
     * Get the model to use for the given script and language.
     */
    private function model(string $script, ?string $language = null): string
    {
        if (blank($language) || $language !== 'en') {
            return 'eleven_turbo_v2_5';
        }

        $abbreviations = [
            'Dr', 'Mr', 'Mrs', 'Ms', 'Prof', 'Sr', 'Jr', 'St', 'Mt', 'Lt', 'Col', 'Gen', 'Sgt',
            'Capt', 'Adm', 'Maj', 'Hon', 'Rev', 'Pres', 'Gov', 'Sen', 'Rep', 'Amb', 'Treas', 'Supt', 'Sec',
            'Ave', 'Blvd', 'Rd', 'Ln', 'Hwy', 'Ctr', 'Plz', 'Ste', 'Dept',
        ];

        $abbreviationsPattern = implode('|', array_map('preg_quote', $abbreviations));

        $pattern = '/\b('.$abbreviationsPattern.')\.?\b|[0-9]+(?:,[0-9]{3})*(?:\.[0-9]+)?/i';

        return preg_match($pattern, $script) ? 'eleven_multilingual_v2' : 'eleven_turbo_v2_5';
    }

    /**
     * Decode the given audio string.
     */
    private function decode(array $audios): string
    {
        return collect($audios)->flatten()->map(function ($audio) {
            if (preg_match('/^data:audio\/[^;]+;base64,/', $audio)) {
                $audio = preg_replace('/^data:audio\/[^;]+;base64,/', '', $audio);
            }

            $audio = preg_replace('/\s+/', '', $audio);

            if (! $segment = base64_decode($audio, strict: true)) {
                throw new RuntimeException('Invalid base64 audio data provided');
            }

            return $segment;
        })->join('');
    }

    /**
     * Split the given script into segments.
     */
    private function splitScript(string $script, ?string $language = null): array
    {
        $language ??= 'en';
        $iterator = IntlBreakIterator::createSentenceInstance($language);
        $iterator->setText($script);

        $boundaries = collect(iterator_to_array($iterator));

        $sentences = $boundaries->map(function (int $end, int $index) use ($boundaries, $script) {
            $start = $index === 0 ? 0 : $boundaries[$index - 1];

            return trim(substr($script, $start, $end - $start));
        })->filter()->values();

        return $sentences->chunk(3)->map(fn ($group) => $group->join(' '))->toArray();
    }

    /**
     * Process the response from ElevenLabs.
     *
     * @throws RuntimeException
     */
    private function process(Response $response): array
    {
        $stream = $response->toPsrResponse()->getBody();

        $buffer = '';
        $results = [];
        while (! $stream->eof()) {
            $chunk = $stream->read(1024);

            if ($chunk === false) {
                continue;
            }

            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $json = substr($buffer, 0, $pos);

                $buffer = substr($buffer, $pos + 1);

                $data = json_decode($json, true);

                if ($data) {
                    $results[] = $data;
                }
            }
        }

        return $results;
    }

    /**
     * ElevenLabs Http client setup.
     */
    private function http(): PendingRequest
    {
        return Http::acceptJson()->timeout(180)
            ->baseUrl(config('services.elevenlabs.url'))
            ->withHeaders(['xi-api-key' => config('services.elevenlabs.key')]);
    }
}
