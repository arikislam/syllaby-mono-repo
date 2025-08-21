<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyHeyGenWebhookSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $content = $request->getContent();
        $secret = config('services.heygen.webhook.secret');

        if (! $request->filled('event_data.callback_id')) {
            return $this->fail('Callback not present from HeyGen webhook.');
        }

        if (! $this->ensureSourceMatches($request)) {
            return $this->fail('Wrong webhook source.');
        }

        if (! $signature = $request->header('signature', null)) {
            return $this->fail('No signature found in headers.');
        }

        $computed = hash_hmac('sha256', $content, $secret);

        if (! hash_equals($computed, $signature)) {
            return $this->fail('No signatures found matching the expected signature for payload.');
        }

        return $next($request);
    }

    /**
     * Check if the source url matches the destination url.
     */
    private function ensureSourceMatches(Request $request): bool
    {
        $destination = sha1(route('heygen.webhook'));
        $callback = json_decode($request->input('event_data.callback_id'), true);

        return hash_equals(Arr::get($callback, 'source'), $destination);
    }

    /**
     * Fails and stops the request with the given reason.
     */
    private function fail(string $reason): Response
    {
        return new Response($reason, 403);
    }
}
