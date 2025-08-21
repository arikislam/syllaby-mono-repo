<?php

namespace App\Syllaby\Scraper\Actions;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\RealClones\RealClone;
use Illuminate\Database\Eloquent\Model;
use App\Syllaby\Scraper\Factory\ContentProviderFactory;
use App\Syllaby\Generators\Actions\ConvertRawContentToScriptAction;

class ExtractScriptAction
{
    const int MAX_WORDS = 20000;

    public function __construct(protected ContentProviderFactory $factory, protected ConvertRawContentToScriptAction $writer) {}

    public function handle(Model|Faceless|RealClone $model, array $input): ?Model
    {
        $generator = $model->generator()->updateOrCreate([], Arr::except($input, ['duration', 'url']));

        $provider = $this->factory->make($url = Arr::get($input, 'url'));

        if (Str::wordCount($content = $provider->extractContent($url)) > static::MAX_WORDS) {
            throw new Exception(__('videos.script_too_long'));
        }

        if (blank($script = $this->writer->handle($generator, $input, $content))) {
            throw new Exception(__('videos.script_failed'));
        }

        return tap($model, function ($model) use ($input, $script) {
            $model->fill($this->getFillable($model, $input, $script));
            $model->save();
        });
    }

    private function getFillable(Model $model, array $input, string $script): array
    {
        return match (true) {
            $model instanceof Faceless => [
                'script' => $script,
                'is_transcribed' => false,
                'estimated_duration' => Arr::get($input, 'duration', 0),
            ],
            default => ['script' => $script],
        };
    }
}
