<?php

namespace App\System\Traits;

use Illuminate\Support\Str;
use LanguageDetector\LanguageDetector;

trait DetectsLanguage
{
    /**
     * Unsupported locales.
     */
    protected array $unsupported = [
        'ar', 'hi', 'bn', 'ru', 'ja',
        'ko', 'th', 'zh', 'he', 'el', 'ta',
        'ur', 'fa', 'si', 'gu', 'hy', 'ka',
        'km', 'lo', 'my', 'te', 'kn', 'ml',
        'bo', 'am',
    ];

    /**
     * Detects the language of the text if no language is provided.
     */
    public function detectLanguage(string $text): string
    {
        $detector = new LanguageDetector;

        return (string) $detector->evaluate($text)->getLanguage();
    }

    /**
     * Maps the language to the locale
     */
    public function locale(?string $language = null): string
    {
        return match (Str::lower($language)) {
            'français' => 'fr',
            'german' => 'de',
            'italian' => 'it',
            'spanish' => 'es',
            'czech' => 'cs',
            'danish' => 'da',
            'dutch' => 'nl',
            'finnish' => 'fi',
            'indonesian' => 'id',
            'polish' => 'pl',
            'portuguese' => 'pt',
            'swedish' => 'sv',
            'turkish' => 'tr',
            'vietnamese' => 'vi',
            'hungarian' => 'hu',
            'filipino' => 'fil',
            'greek' => 'el',
            'russian' => 'ru',
            'japanese' => 'ja',
            'korean' => 'ko',
            'hindi' => 'hi',
            'mandarin' => 'zh',
            'arabic' => 'ar',
            'persian' => 'fa',
            default => 'en',
        };
    }

    /**
     * Checks if the language is supported.
     */
    public function hasLanguageSupport(string $locale): bool
    {
        return ! in_array($locale, $this->unsupported);
    }
}
