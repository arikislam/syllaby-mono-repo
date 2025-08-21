<?php

namespace App\Shared\TikTok;

use Illuminate\Support\Facades\Facade;

class TikTok extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TikTokPixel::class;
    }
}
