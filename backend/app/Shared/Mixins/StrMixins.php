<?php

namespace App\Shared\Mixins;

use Closure;
use Illuminate\Support\Str;

class StrMixins
{
    public function limitNaturally(): Closure
    {
        return function (string $value, int $limit = 100): string {
            if (Str::length($value) <= $limit || $limit === 0) {
                return $value;
            }

            $truncated = '';

            $segments = preg_split('/([!?.;,])/', $value, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

            foreach ($segments as $segment) {
                if (Str::length($truncated.$segment) > $limit) {
                    break;
                }
                $truncated .= $segment;
            }

            if (filled($truncated) && Str::length($truncated) > ($limit * 0.6)) {
                return rtrim($truncated);
            }

            $limit = $limit - 1;

            $lastSpace = mb_strrpos(Str::substr($value, 0, $limit), ' ');

            if ($lastSpace !== false) {
                return Str::substr($value, 0, $lastSpace).'.';
            }

            return Str::substr($value, 0, $limit).'.';
        };
    }
}
