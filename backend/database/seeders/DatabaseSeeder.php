<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            IndustryTableSeeder::class,
            SystemUserSeeder::class,
            TagTableSeeder::class,
            SurveyTableSeeder::class,
            TemplateTableSeeder::class,
            CreditEventTableSeeder::class,
            AssetTableSeeder::class,
            MetricsTableSeeder::class,
            EditorAssetTableSeeder::class,
            CharacterAndGenreSeeder::class,
        ]);
    }
}
