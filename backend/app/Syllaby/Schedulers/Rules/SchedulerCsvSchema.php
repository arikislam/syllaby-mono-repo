<?php

namespace App\Syllaby\Schedulers\Rules;

use Closure;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\LazyCollection;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Syllaby\Schedulers\Concerns\InteractsWithCsv;

class SchedulerCsvSchema implements ValidationRule
{
    use InteractsWithCsv;

    /**
     * Maximum number of rows allowed in the CSV.
     */
    protected const int MAX_ROWS = 150;

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The :attribute must be a valid CSV file.');

            return;
        }

        try {
            $delimiter = $this->resolveDelimiter($value);
            $reader = SimpleExcelReader::create($value->getPathname(), 'csv')->useDelimiter($delimiter);
            $rows = $reader->getRows();

            if (! $headers = $reader->getHeaders()) {
                $fail('The CSV file is empty or could not be parsed.');

                return;
            }

            $this->headers($headers, $fail);
            $this->rows($rows, $fail);
            $this->data($rows, $fail);
        } catch (Exception $exception) {
            $fail('The CSV file could not be parsed. Please ensure it is a valid CSV file.');
        }
    }

    /**
     * Validate that the CSV has the required headers.
     */
    private function headers(array $headers, Closure $fail): void
    {
        $required = collect(self::REQUIRED_HEADERS);
        $normalized = collect($headers)->map(fn ($header) => Str::of($header)->lower()->trim()->value())->values();

        $missing = $required->diff($normalized);

        if ($missing->isNotEmpty()) {
            $fail("The CSV must contain the following columns: {$missing->implode(', ')}.");
        }
    }

    /**
     * Validate the number of rows in the CSV.
     */
    private function rows(LazyCollection $rows, Closure $fail): void
    {
        $total = $rows->count();

        match (true) {
            $total === 0 => $fail('The CSV must contain at least one data row.'),
            $total > self::MAX_ROWS => $fail('The CSV cannot contain more than '.self::MAX_ROWS.' rows.'),
            default => null,
        };
    }

    /**
     * Validate the data in each row.
     */
    private function data(LazyCollection $rows, Closure $fail): void
    {
        $rows->each(function ($row, $index) use ($fail) {
            $this->validateRowData($row, $index + 1, $fail);
        });
    }

    /**
     * Validate individual row data.
     */
    private function validateRowData(array $row, int $cursor, Closure $fail): void
    {
        $title = Arr::get($row, 'Video Title');
        $script = Arr::get($row, 'Script');
        $caption = Arr::get($row, 'Caption');

        match (true) {
            blank($title) => $fail("Row {$cursor}: Title cannot be empty."),
            strlen($title) > 255 => $fail("Row {$cursor}: Title cannot exceed 255 characters."),
            blank($script) => $fail("Row {$cursor}: Script cannot be empty."),
            filled($caption) && strlen($caption) > 255 => $fail("Row {$cursor}: Caption cannot exceed 255 characters."),
            default => null,
        };
    }
}
