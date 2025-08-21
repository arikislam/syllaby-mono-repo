<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class PaidCustomersMiddleware
{
    use ApiResponse;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->subscribed()) {
            return $next($request);
        }

        return $this->respondWithError(
            message: 'You need to have an active subscription.',
            status: Response::HTTP_PAYMENT_REQUIRED,
            code: 'GEN-PAYMENT-ERROR'
        );
    }
}
