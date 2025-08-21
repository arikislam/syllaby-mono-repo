<?php

namespace Database\Seeders;

use Exception;
use Illuminate\Support\Str;
use App\Syllaby\Surveys\Survey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SurveyTableSeeder extends Seeder
{
    /**
     * List of survey stubs
     */
    protected array $files = [
        'content-goals.json',
        'user-experience.json',
        'business-intake.json',
        'cancellation-reason.json',
        'cancellation-reason-lite.json',
    ];

    /**
     * Run the database seeds.
     *
     * @throws Exception
     */
    public function run(): void
    {
        foreach ($this->files as $file) {
            $this->handle($file);
        }
    }

    /**
     * Handles the creation each survey.
     *
     * @throws Exception
     */
    protected function handle(string $file): void
    {
        $data = $this->loadFile($file);

        tap($this->createSurvey($data['survey']), function ($survey) use ($data) {
            $this->createQuestions($survey, $data['questions']);
        });
    }

    /**
     * Loads the given survey from disk.
     *
     * @throws Exception
     */
    private function loadFile(string $filename): array
    {
        $path = storage_path("data/surveys/{$filename}");

        if (! File::exists($path)) {
            throw new Exception("No file: <{$filename}> exists.");
        }

        return json_decode(File::get($path), true);
    }

    /**
     * Create in storage the survey.
     */
    private function createSurvey(array $data): Survey
    {
        return Survey::updateOrCreate(
            ['slug' => Str::slug($data['name'])],
            [...$data]
        );
    }

    /**
     * Create in storage the given survey questions.
     */
    private function createQuestions(Survey $survey, array $questions): void
    {
        foreach ($questions as $question) {
            $survey->questions()->updateOrCreate(
                ['slug' => Str::slug($question['title'], '_')],
                [...$question, 'options' => $this->formatOptions($question['options'])]
            );
        }
    }

    /**
     * Formats the options array
     */
    private function formatOptions(?array $options): ?array
    {
        if (blank($options)) {
            return null;
        }

        return array_reduce($options, function ($all, $option) {
            return array_merge($all, [Str::slug($option) => $option]);
        }, []);
    }
}
