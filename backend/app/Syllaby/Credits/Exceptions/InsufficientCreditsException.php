<?php

namespace App\Syllaby\Credits\Exceptions;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;

class InsufficientCreditsException extends Exception
{
    use ApiResponse;

    /**
     * Create a new insufficient credits exception instance.
     */
    public function __construct(string $message = 'Insufficient credits available')
    {
        parent::__construct($message, Response::HTTP_FORBIDDEN, null);
    }

    /**
     * Report the exception.
     */
    public function report(): void {}

    /**
     * Render the exception into an HTTP response.
     */
    public function render(): JsonResponse
    {
        return $this->errorInsufficientCredits($this->getMessage());
    }
}
