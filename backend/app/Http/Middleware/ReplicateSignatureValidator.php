<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class ReplicateSignatureValidator
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->header('webhook-id');
        $timestamp = (int) $request->header('webhook-timestamp');
        $signature = $request->header('webhook-signature');

        if (blank($id) || blank($timestamp) || blank($signature)) {
            return response()->json(['message' => 'Invalid request'], 400);
        }

        if (! $secret = config('services.replicate.webhook.secret')) {
            return response()->json(['message' => 'Invalid request'], 400);
        }

        $secret = base64_decode(Str::of($secret)->after('_'));
        $content = "{$id}.{$timestamp}.{$request->getContent()}";
        $computed = base64_encode(hash_hmac('sha256', $content, $secret, true));

        $expected = collect(explode(' ', $signature))->map(fn ($sig) => explode(',', $sig)[1]);

        if (! $this->hasValidSignature($expected, $computed)) {
            return response()->json(['error' => 'Invalid webhook signature.'], 400);
        }

        if (abs(time() - $timestamp) >= 60 * 60) {
            return response()->json(['error' => 'Invalid webhook timestamp.'], 400);
        }

        return $next($request);
    }

    /**
     * Check if the computed signature is in the expected list.
     */
    private function hasValidSignature(Collection $expected, string $computed): bool
    {
        return $expected->some(fn ($sig) => hash_equals($computed, $sig));
    }
}
