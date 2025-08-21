<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class AddRequestContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Context::add('Url', $request->fullUrl());
        Context::add('User-ID', $request->user()?->id);
        Context::add('User-Name', $request->user()?->name);

        if ($request->method() === SymfonyRequest::METHOD_POST) {
            $input = $this->hideSensitiveAttributes($request->input());
            Context::add('Request', $input);
        }

        return $next($request);
    }

    private function hideSensitiveAttributes(array $input): array
    {
        return collect($input)->mapWithKeys(function ($value, $key) {
            if (in_array($key, ['password', 'password_confirmation', 'current_password'])) {
                return [$key => str_repeat('*', strlen($value))];
            }

            return [$key => $value];
        })->toArray();
    }
}
