<?php

namespace App\Syllaby\Clonables\Vendors\Voices;

use Throwable;
use FFMpeg\FFMpeg;
use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use FFMpeg\Format\Audio\Mp3;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use App\Syllaby\Clonables\Contracts\RecorderContract;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Elevenlabs implements RecorderContract
{
    /**
     * Clones a voice from a sample.
     *
     * @throws RequestException|ConnectionException
     */
    public function clone(Voice $voice, Clonable $clonable): ?Voice
    {
        $payload = [
            ['name' => 'name', 'contents' => $voice->name],
            ['name' => 'labels', 'contents' => $this->labels($voice)],
            ['name' => 'description', 'contents' => Arr::get($clonable->metadata, 'description')],
        ];

        $samples = $clonable->getMedia()->map(function ($sample) use ($clonable) {
            if (! in_array($sample->mime_type, ['video/webm', 'audio/webm'])) {
                return $sample;
            }

            return $this->convertToMp3($sample, $clonable);
        });

        $files = $samples->map(fn ($sample) => [
            'name' => 'files',
            'filename' => $sample->file_name,
            'contents' => Storage::disk($sample->disk)->get($sample->getPathRelativeToRoot()),
        ])->toArray();

        $response = $this->http()->asMultipart()->post('/voices/add', [
            ...$payload, ...$files,
        ]);

        if ($response->failed()) {
            return tap(null, fn () => Log::error('Error while cloning voice with ElevenLabs.'));
        }

        $preview = $this->forceVoicePreview($response->json('voice_id'));

        return tap($voice)->update([
            'is_active' => true,
            'provider_id' => $response->json('voice_id'),
            'preview_url' => Arr::get($preview, 'preview_url'),
            'metadata' => Arr::only($preview, ['labels', 'samples', 'settings', 'fine_tuning', 'description']),
        ]);
    }

    /**
     * Remove from provider a given voice clone.
     *
     * @throws ConnectionException
     */
    public function remove(Voice $voice): bool
    {
        $response = $this->http()->asJson()->delete("/voices/{$voice->provider_id}");

        if ($response->failed()) {
            return tap(false, fn () => Log::error('Unable to delete voice clone with ElevenLabs'));
        }

        return true;
    }

    /**
     * Build json string with labels.
     */
    private function labels(Voice $voice): string
    {
        return json_encode([
            'env' => app()->environment(),
            'gender' => $voice->gender,
            'user_id' => (string) $voice->user_id,
        ]);
    }

    /**
     * Sends some text to Elevenlabs to force a voice preview to be created.
     *
     * @throws ConnectionException
     */
    private function forceVoicePreview(string $voiceId): array
    {
        $this->http()->asJson()->post("/text-to-speech/{$voiceId}", [
            'text' => 'Hello. I am an automated text.',
            'model_id' => 'eleven_turbo_v2_5',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
            ],
        ]);

        do {
            sleep(1);
            $response = $this->http()->asJson()->get("/voices/{$voiceId}");
        } while ($response->json('preview_url') === null);

        return $response->json();
    }

    /**
     * Convert browser recorded samples to MP3
     */
    private function convertToMp3(Media $sample, Clonable $clonable): ?Media
    {
        $tmp = Str::random(20);
        $output = Storage::disk('local')->path("uploads/samples/{$sample->id}/{$tmp}.mp3");

        if (Storage::disk('local')->directoryMissing("uploads/samples/{$sample->id}")) {
            Storage::disk('local')->makeDirectory("uploads/samples/{$sample->id}");
        }

        try {
            $file = $this->ffmpeg()->open($sample->getFullUrl());
            $file->save(new Mp3, $output);
        } catch (RuntimeException $exception) {
            Log::error('Fail to convert recorded audio to mp3', ['reason' => $exception->getMessage()]);
            Storage::disk('local')->deleteDirectory("uploads/samples/{$sample->id}");

            return null;
        }

        if (! $media = $this->uploadConvertedSample($output, $clonable)) {
            return null;
        }

        Storage::disk('local')->deleteDirectory("uploads/samples/{$sample->id}");
        $sample->delete();

        return $media;
    }

    /**
     * Create in storage and upload converted audio sample to cloud.
     */
    private function uploadConvertedSample(string $path, Clonable $clonable): ?Media
    {
        try {
            return $clonable->addMedia($path)->withAttributes([
                'user_id' => $clonable->user_id,
            ])->toMediaCollection();
        } catch (Throwable $exception) {
            return tap(null, fn () => Log::error($exception->getMessage()));
        }
    }

    /**
     * FFMpeg instance
     */
    private function ffmpeg(): FFMpeg
    {
        return FFMpeg::create([
            'timeout' => 1140,
            'ffmpeg.threads' => 2,
            'ffmpeg.binaries' => config('media-library.ffmpeg_path'),
            'ffprobe.binaries' => config('media-library.ffprobe_path'),
        ]);
    }

    /**
     * Elevenlabs Http client setup.
     */
    private function http(): PendingRequest
    {
        return Http::baseUrl(config('services.elevenlabs.url'))->withHeaders([
            'xi-api-key' => config('services.elevenlabs.key'),
        ]);
    }
}
