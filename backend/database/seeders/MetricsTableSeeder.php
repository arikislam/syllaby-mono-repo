<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MetricsTableSeeder extends Seeder
{
    public array $data = [
        ['name' => 'Views Count', 'description' => 'Number of views for a publication'],
        ['name' => 'Likes Count', 'description' => 'Number of likes for a publication'],
        ['name' => 'Comments Count', 'description' => 'Number of comments for a publication'],
        ['name' => 'Dislikes Count', 'description' => 'Number of dislikes for a publication'],
        ['name' => 'Shares Count', 'description' => 'Number of shares for a publication'],
        ['name' => 'Favorites Count', 'description' => 'Number of favorites for a publication'],
    ];

    public function run(): void
    {
        $data = collect($this->data)->map(fn ($metric) => array_merge($metric, [
            'slug' => str()->slug($metric['name']),
            'created_at' => now(),
            'updated_at' => now(),
        ]))->toArray();

        DB::table('publication_metric_keys')->upsert($data, ['slug'], ['name', 'description', 'updated_at']);

        $this->command->info('Metrics seeded successfully.');
    }
}
