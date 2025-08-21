<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JVZooSignatureValidator
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->verify($request->all())) {
            return response()->json(['message' => 'Invalid JVZoo signature verification'], 400);
        }

        return $next($request);
    }

    /**
     * Verify JVZoo webhook signature using their official verification method.
     */
    private function verify(array $payload): bool
    {
        if (! $secret = config('services.jvzoo.webhook_secret', null)) {
            return false;
        }

        if (! Arr::has($payload, 'cverify')) {
            return false;
        }

        $fields = array_keys(Arr::except($payload, ['cverify']));
        sort($fields);

        $pop = collect($fields)->map(fn ($field) => Arr::get($payload, $field, ''))
            ->push($secret)
            ->implode('|');

        if (mb_detect_encoding($pop) != 'UTF-8') {
            $pop = mb_convert_encoding($pop, 'UTF-8');
        }

        $hash = strtoupper(substr(sha1($pop), 0, 8));

        return $hash === Arr::get($payload, 'cverify');
    }
}
