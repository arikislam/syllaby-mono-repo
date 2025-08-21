<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Syllaby\Surveys\Industry;

class IndustryTableSeeder extends Seeder
{
    protected array $industries = [
        'Legal',
        'Energy',
        'Retail ',
        'Defense',
        'Finance',
        'Aerospace',
        'Education',
        'Consulting',
        'Government',
        'Healthcare',
        'Non-Profit',
        'Technology',
        'Agriculture',
        'Hospitality',
        'Real Estate',
        'Construction',
        'Manufacturing',
        'Transportation',
        'Pharmaceuticals',
        'Media and Entertainment',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = collect($this->industries)->map(fn ($industry) => [
            'name' => $industry,
            'slug' => Str::slug($industry),
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        Industry::upsert($data, ['slug'], ['name', 'slug']);
    }
}
