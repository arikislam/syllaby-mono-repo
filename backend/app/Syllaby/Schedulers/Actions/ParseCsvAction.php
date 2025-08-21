<?php

namespace App\Syllaby\Schedulers\Actions;

use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use Spatie\SimpleExcel\SimpleExcelReader;
use App\Syllaby\Schedulers\Concerns\InteractsWithCsv;

class ParseCsvAction
{
    use InteractsWithCsv;

    /**
     * Parse the uploaded CSV file and return structured data.
     */
    public function handle(UploadedFile $csv): array
    {
        $delimiter = $this->resolveDelimiter($csv);
        $reader = SimpleExcelReader::create($csv->getPathname(), 'csv')->useDelimiter($delimiter);

        return $reader->getRows()->map(fn (array $row) => [
            'title' => Arr::get($row, 'Video Title'),
            'script' => Arr::get($row, 'Script'),
            'caption' => Arr::get($row, 'Caption'),
        ])->toArray();
    }
}
