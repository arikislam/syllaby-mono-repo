<?php

namespace App\Syllaby\Schedulers\Concerns;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

trait InteractsWithCsv
{
    /**
     * Required CSV headers for scheduler bulk import.
     */
    protected const array REQUIRED_HEADERS = ['video title', 'script'];

    /**
     * Resolve the delimiter for the CSV file.
     */
    private function resolveDelimiter(UploadedFile $file): string
    {
        $line = file($file->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)[0] ?? '';

        if (empty($line)) {
            return ',';
        }

        $delimiters = [',', ';', "\t", '|'];
        $expected = collect(self::REQUIRED_HEADERS)->map(fn ($header) => Str::lower($header));

        foreach ($delimiters as $delimiter) {
            $fields = collect(str_getcsv($line, $delimiter))->map(fn ($field) => Str::lower(trim($field)));

            if ($expected->intersect($fields)->count() === $expected->count()) {
                return $delimiter;
            }
        }

        return ',';
    }
}
