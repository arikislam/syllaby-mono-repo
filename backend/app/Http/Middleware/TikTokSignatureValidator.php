<?php

namespace App\Http\Middleware;

use Arr;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TikTokSignatureValidator
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort_if(! $request->hasHeader('TikTok-Signature'), Response::HTTP_FORBIDDEN, 'Invalid Signature');

        $header = $request->header('TikTok-Signature');

        [$timestamp, $signature] = $this->parse($header);

        $signedPayload = sprintf('%s.%s', $timestamp, $request->getContent());

        $expected = hash_hmac('sha256', $signedPayload, config('services.tiktok.client_secret'));

        abort_if(! hash_equals($signature, $expected), Response::HTTP_FORBIDDEN, 'Invalid Signature');

        abort_if($this->requestTooOld($timestamp), Response::HTTP_FORBIDDEN, 'Signature is too old');

        return $next($request);
    }

    private function parse(string $header): array
    {
        $header = explode(',', $header);

        abort_if(count($header) !== 2, 403, 'Invalid Signature');

        $timestamp = Arr::get(explode('=', $header[0]), 1);

        $signature = Arr::get(explode('=', $header[1]), 1);

        return [$timestamp, $signature];
    }

    private function requestTooOld(mixed $timestamp): bool
    {
        $received = Carbon::createFromTimestamp($timestamp);

        $limit = Carbon::now()->subMinutes(config('services.tiktok.webhook_threshold'));

        return $received->lessThan($limit);
    }
}
