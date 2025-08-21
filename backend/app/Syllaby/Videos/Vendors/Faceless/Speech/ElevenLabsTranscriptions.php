<?php

namespace App\Syllaby\Videos\Vendors\Faceless\Speech;

use Illuminate\Support\Arr;

class ElevenLabsTranscriptions
{
    /**
     * Process the transcription JSON data into words with corrected timestamps
     */
    public function parse(array $transcriptions): array
    {
        $segments = collect($transcriptions)->reduce(function ($carry, $segments) {
            $carry[] = [
                'text' => Arr::collapse(Arr::pluck($segments, 'characters')),
                'start' => Arr::collapse(Arr::pluck($segments, 'character_start_times_seconds')),
                'end' => Arr::collapse(Arr::pluck($segments, 'character_end_times_seconds')),
            ];

            return $carry;
        }, []);

        $words = [];
        $offset = 0.0;

        foreach ($segments as $segment) {
            [$segmentWords, $segmentEnd] = $this->buildWords($segment, $offset);
            $words = array_merge($words, $segmentWords);
            $offset = $segmentEnd;
        }

        return array_values(
            array_filter($words, fn ($word) => filled($word['text']))
        );
    }

    private function buildWords(array $item, float $offset): array
    {
        $words = [];

        $chars = array_map(null, $item['text'], $item['start'], $item['end']);

        $chunks = array_reduce($chars, function ($carry, $char) use ($offset) {
            $index = count($carry) - 1;
            if ($index < 0 || $char[0] === ' ') {
                $carry[] = [[$char[0]], $char[1] + $offset, $char[2] + $offset];
            } else {
                $carry[$index][0][] = $char[0];
                $carry[$index][2] = $char[2] + $offset;
            }

            return $carry;
        }, []);

        foreach ($chunks as $chunk) {
            $words[] = [
                'start' => round($chunk[1], 4),
                'end' => round($chunk[2], 4),
                'text' => $this->normalizePauses($chunk[0]),
            ];
        }

        return [$words, $words[count($words) - 1]['end']];
    }

    /**
     * Normalize pauses in the text
     */
    private function normalizePauses(array $chunk): string
    {
        $text = trim(implode('', $chunk));

        return $text === '.' ? '' : $text;
    }
}
