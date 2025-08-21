<?php

namespace App\Syllaby\Videos\Actions;

use Exception;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Generators\Vendors\Transcribers\Transcriber;

class TranscribeAudioAction
{
    /**
     * Handle the transcription action.
     *
     * @throws Exception
     */
    public function handle(Faceless $faceless, array $input): Faceless
    {
        $transcription = Transcriber::driver('whisper')->run(Arr::get($input, 'url'), [
            'language' => 'none',
        ]);

        if (! $transcription) {
            throw new Exception('Failed to transcribe audio');
        }

        return DB::transaction(function () use ($faceless, $transcription) {
            $faceless->captions()->updateOrCreate([], [
                'provider' => 'whisper',
                'user_id' => $faceless->user_id,
                'content' => $this->segmentation($transcription->captions),
            ]);

            return tap($faceless)->update([
                'is_transcribed' => true,
                'script' => Arr::get($transcription->captions, 'text'),
            ]);
        });
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
