<?php

namespace Database\Seeders;

use App\Syllaby\Tags\Tag;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class TagTableSeeder extends Seeder
{
    protected array $tags = [
        'Legal',
        'Finance',
        'Education',
        'Ecommerce',
        'Healthcare',
        'Real Estate',
        'Entertainment',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->tags as $tag) {
            Tag::updateOrCreate(['slug' => Str::slug($tag)], ['name' => $tag]);
        }
    }
}
