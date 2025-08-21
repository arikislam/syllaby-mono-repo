<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     *
     * @param  Request  $request
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof AuthenticationException && $request->wantsJson()) {
            return $this->errorUnauthorized($e->getMessage(), $this->code($e));
        }

        if ($e instanceof AuthorizationException && $request->wantsJson()) {
            return $this->errorForbidden($e->getMessage(), $this->code($e));
        }

        if ($e instanceof ModelNotFoundException && $request->wantsJson()) {
            return $this->errorNotFound($e->getMessage(), $this->code($e));
        }

        if ($e instanceof FileIsTooBig && $request->wantsJson()) {
            return $this->errorWrongArgs('File is Too Big', Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        if ($e instanceof FileDoesNotExist && $request->wantsJson()) {
            return $this->errorNotFound('File Does Not Exists at the given path');
        }

        return parent::render($request, $e);
    }

    /**
     * Set the internal code for the http response.
     */
    private function code(Exception $exception): ?string
    {
        return $exception->getCode() ?: null;
    }
}
