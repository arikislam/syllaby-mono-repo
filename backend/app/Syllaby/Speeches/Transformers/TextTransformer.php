<?php

namespace App\Syllaby\Speeches\Transformers;

use Exception;
use Illuminate\Support\Str;

class TextTransformer
{
    private array $replacements = [
        // Smart quotes to ASCII
        '“' => '"',
        '”' => '"',
        '‘' => "'",
        '’' => "'",

        // Dashes and punctuation
        '…' => '...',
        '—' => '-',
        '–' => '-',

        // Invisible characters
        "\u{00A0}" => ' ',
        "\u{200B}" => '',
        "\u{200C}" => '',
        "\u{200D}" => '',
        "\u{FEFF}" => '',

        // Line endings
        "\r\n" => "\n",
        "\r" => "\n",
    ];

    /**
     * @throws Exception
     */
    public function format(string $text, string $provider): string
    {
        return match (Str::lower($provider)) {
            'elevenlabs' => $this->elevenLabs($text),
            default => throw new Exception('Invalid provider'),
        };
    }

    /**
     * Replace pause tags with <break time="0.5s" /> tags.
     *
     * @throws Exception
     */
    protected function elevenLabs(string $text): string
    {
        $text = $this->sanitize($text);

        return preg_replace('/\[pause\s+(\d+(?:\.\d+)?(?:ms|s)?)\]/i', '<break time="$1" />', $text);
    }

    /**
     * Sanitize text by replacing special characters with ASCII equivalents.
     */
    private function sanitize(string $text): string
    {
        if (empty(trim($text))) {
            return '';
        }

        $text = strtr($text, $this->replacements);

        $text = $this->removeEmojis($text);
        $text = $this->normalizePauses($text);
        $text = $this->normalizeEmphasis($text);

        return preg_replace('/\s+/u', ' ', trim($text));
    }

    private function removeEmojis(string $text): string
    {
        return preg_replace('/[\p{Emoji_Presentation}\p{Extended_Pictographic}]/u', '', $text);
    }

    /**
     * Normalize pause directives for ElevenLabs.
     */
    private function normalizePauses(string $text): string
    {
        return preg_replace_callback(
            pattern: '/\[pause\s*([0-9]*\.?[0-9]+)(?:s|sec|second|seconds)?\s*\]/i',
            callback: fn ($match) => "[pause {$match[1]}s]",
            subject: $text
        );
    }

    /**
     * Normalize emphasis and clean up parenthetical asides.
     */
    private function normalizeEmphasis(string $text): string
    {
        $text = preg_replace_callback(
            pattern: '/\b[A-Z]{3,}\b/',
            callback: fn ($match) => '<emphasis level="strong">'.ucfirst(strtolower($match[0])).'</emphasis>',
            subject: $text
        );

        $text = preg_replace('/\s*\([^)]*\)\s*/', ' ', $text);

        return $text;
    }
}
