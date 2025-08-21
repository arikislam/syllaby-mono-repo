<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     */
    protected $except = [
        'stripe/*',
        'external/*',
        'd-id/webhook',
        'heygen/webhook',
        'tiktok/webhook',
        'facegen/redirect',
        'creatomate/webhook',
        'fastvideo/webhook/*',
        'minimax/webhook',
        'replicate/webhook',
        'ses/webhook',
        'character-consistency/webhook',
        'remotion/webhook',
        'google-play/webhook',
        'custom-character/webhook/*',
        'jvzoo/webhook',
    ];
}
