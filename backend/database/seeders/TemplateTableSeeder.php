<?php

namespace Database\Seeders;

use App\Syllaby\Tags\Tag;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Syllaby\Templates\Template;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;

class TemplateTableSeeder extends Seeder
{
    protected array $files = [
        'creatomate.json',
    ];

    /**
     * Runs the seeder
     *
     * @throws FileCannotBeAdded
     */
    public function run(): void
    {
        foreach ($this->files as $file) {
            if (!$data = $this->loadFile($file)) {
                continue;
            }

            foreach ($data as $item) {
                tap($this->persist($item), function ($template) use ($item) {
                    $this->attachTags($template, $item);
                    $this->createPreview($template, $item);
                });
            }
        }
    }

    /**
     * Reads from disk the given template stubs file.
     */
    private function loadFile(string $file): ?array
    {
        $path = storage_path("data/templates/{$file}");

        return File::exists($path) ? json_decode(File::get($path), true) : null;
    }

    /**
     * Persist in storage the model attributes.
     */
    private function persist(array $item): Template
    {
        $lookup = [
            'name' => $name = Arr::get($item, 'name'),
            'type' => $type = Arr::get($item, 'type'),
            'slug' => sprintf('%s-%s', Str::slug($name), $type),
        ];

        $attributes = [
            'description' => Arr::get($item, 'description'),
            'metadata' => Arr::get($item, 'metadata'),
            'source' => Arr::get($item, 'source'),
            'is_active' => Arr::get($item, 'is_active', true),
        ];

        return Template::updateOrCreate($lookup, $attributes);
    }

    /**
     * Attach tags to given template when present.
     */
    private function attachTags(Template $template, array $item): void
    {
        if (!Arr::exists($item, 'tags')) {
            return;
        }

        if ($tags = Tag::whereIn('slug', Arr::get($item, 'tags'))->pluck('id')) {
            $template->tags()->sync($tags);
        }
    }

    /**
     * Creates the template preview.
     *
     * @throws FileCannotBeAdded
     */
    private function createPreview(Template $template, array $item): void
    {
        if (!Arr::exists($item, 'preview')) {
            return;
        }

        $template->addMediaFromUrl(Arr::get($item, 'preview'))
            ->addCustomHeaders(['ACL' => 'public-read'])
            ->toMediaCollection('preview');
    }
}
