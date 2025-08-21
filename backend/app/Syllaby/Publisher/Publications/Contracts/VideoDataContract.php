<?php

namespace App\Syllaby\Publisher\Publications\Contracts;

use Illuminate\Contracts\Support\Arrayable;

interface VideoDataContract extends Arrayable
{
    public static function fromArray(array $data): self;

    public function toArray(): array;
}