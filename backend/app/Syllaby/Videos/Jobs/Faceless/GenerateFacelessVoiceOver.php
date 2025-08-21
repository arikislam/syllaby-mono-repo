<?php

namespace App\Syllaby\Videos\Jobs\Faceless;

use Exception;
use Throwable;
use Illuminate\Support\Arr;
use Illuminate\Bus\Batchable;
use App\Syllaby\Speeches\Voice;
use App\System\Enums\QueueType;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\Log;
use App\System\Traits\DetectsLanguage;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Http\Client\ConnectionException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Syllaby\Videos\Events\FacelessGenerationFailed;
use App\Syllaby\Videos\Vendors\Faceless\Speech\Elevenlabs;

class GenerateFacelessVoiceOver implements ShouldBeUnique, ShouldQueue
{
    use Batchable, DetectsLanguage, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Faceless $faceless)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::FACELESS->value);
    }

    /**
     * Execute the job.
     *
     * @throws ConnectionException
     */
    public function handle(Elevenlabs $elevenlabs): void
    {
        if ($this->faceless->is_transcribed) {
            return;
        }

        $this->faceless->clearMediaCollection('script');

        $this->faceless->load(['voice', 'generator']);

        $config = [
            'voice_type' => $this->faceless->voice->type,
            'voice_id' => $this->faceless->voice->provider_id,
            'language' => $this->language($this->faceless->voice),
        ];

        if (! $result = $elevenlabs->generate($this->faceless->script, $config)) {
            $this->release();

            return;
        }

        if (! $media = $this->download($result['audio'])) {
            $this->release();

            return;
        }

        $this->faceless->update(['options->voiceover' => $media->id]);
        $this->faceless->captions()->updateOrCreate([], [
            'provider' => 'elevenlabs',
            'user_id' => $this->faceless->user_id,
            'content' => $this->segmentation($result['captions']),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Faceless [{id}] - Voiceover generation failed', [
            'id' => $this->faceless->id,
            'error' => $exception->getMessage(),
        ]);

        event(new FacelessGenerationFailed($this->faceless));
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->faceless->id;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ["faceless-voiceover:{$this->faceless->id}"];
    }

    /**
     * Resolve the script language when provided.
     */
    private function language(Voice $voice): ?string
    {
        if ($voice->type === Voice::REAL_CLONE) {
            return null;
        }

        return $this->locale($this->faceless->generator?->language);
    }

    /**
     * Download the given audio and link it to the faceless script collection.
     */
    private function download(string $audio): ?Media
    {
        try {
            return $this->faceless->addMediaFromStream($audio)
                ->withAttributes(['user_id' => $this->faceless->user_id])
                ->usingFileName('voiceover.mp3')
                ->usingName('voiceover')
                ->toMediaCollection('script');
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Build the segments from the captions.
     */
    private function segmentation(array $captions): array
    {
        return collect($captions['words'])->chunkWhile(function ($word, $key, $chunk) {
            return (Arr::get($word, 'end') - Arr::get($chunk->first(), 'start')) <= 5;
        })->map(fn ($chunk) => [
            'text' => $chunk->pluck('text')->implode(' '),
            'start' => Arr::get($chunk->first(), 'start'),
            'end' => Arr::get($chunk->last(), 'end'),
            'words' => $chunk->values()->toArray(),
        ])->values()->toArray();
    }
}
