<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Editor\EditorAsset;
use Illuminate\Support\Facades\Validator;

class EditorAssetTableSeeder extends Seeder
{
    protected array $files = [
        'assets.json',
        'fonts.json',
    ];

    public function run(): void
    {
        foreach ($this->files as $file) {
            if (! $data = $this->loadFile($file)) {
                return;
            }

            DB::transaction(fn () => Collection::make($data)->each(fn ($asset) => $this->persist($asset)));
        }
    }

    private function loadFile(string $filename): array
    {
        $path = storage_path("data/editor/{$filename}");

        return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    }

    private function persist(array $asset): void
    {
        $validated = Validator::validate($asset, [
            'type' => ['required'],
            'preview' => ['required'],
            'key' => ['required'],
            'value' => ['required', 'array'],
        ]);

        $unique = [
            'key' => $validated['key'],
            'type' => $validated['type'],
        ];

        EditorAsset::updateOrCreate($unique, [
            'preview' => $validated['preview'],
            'value' => $validated['value'],
            'active' => true,
        ]);
    }
}
