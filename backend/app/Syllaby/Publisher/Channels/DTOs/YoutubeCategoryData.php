<?php

namespace App\Syllaby\Publisher\Channels\DTOs;

readonly class YoutubeCategoryData
{
    public function __construct(
        public string $id,
        public string $title,
    ) {

    }
}