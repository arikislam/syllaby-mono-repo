<?php

namespace App\Shared\Facebook;

use Illuminate\Support\Facades\Facade;

class Pixel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FacebookPixel::class;
    }
}
