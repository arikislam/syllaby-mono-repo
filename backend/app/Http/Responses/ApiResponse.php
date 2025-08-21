<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;

trait ApiResponse
{
    /**
     * Generates a Response 403 HTTP header with custom error code when a feature is not supported.
     */
    public function errorUnsupportedFeature(string $message = 'Feature not supported by current plan.'): JsonResponse
    {
        return $this->errorForbidden($message, ErrorCode::FEATURE_NOT_ALLOWED->value);
    }

    /**
     * Generates a Response 403 HTTP header with custom error code when a feature is not supported.
     */
    public function errorInsufficientCredits(string $message = 'Insufficient credits to perform this action.'): JsonResponse
    {
        return $this->errorForbidden($message, ErrorCode::INSUFFICIENT_CREDITS->value);
    }

    /**
     * Generates a Response with a 403 HTTP header and a given message.
     */
    public function errorForbidden(string $message = 'Forbidden', ?string $code = null): JsonResponse
    {
        $code ??= ErrorCode::GEN_FORBIDDEN->value;

        return $this->respondWithError($message, Response::HTTP_FORBIDDEN, $code);
    }

    /**
     * Generates a Response with a 500 HTTP header and a given message.
     */
    public function errorInternalError(string $message = 'Internal Error', ?string $code = null): JsonResponse
    {
        $code ??= ErrorCode::GEN_API_ERROR->value;

        return $this->respondWithError($message, Response::HTTP_INTERNAL_SERVER_ERROR, $code);
    }

    /**
     * Generates a Response with a 404 HTTP header and a given message.
     */
    public function errorNotFound(string $message = 'Resource Not Found', ?string $code = null): JsonResponse
    {
        $code ??= ErrorCode::GEN_NOT_FOUND->value;

        return $this->respondWithError($message, Response::HTTP_NOT_FOUND, $code);
    }

    /**
     * Generates a Response with a 401 HTTP header and a given message.
     */
    public function errorUnauthorized(string $message = 'Unauthorized', ?string $code = null): JsonResponse
    {
        $code ??= ErrorCode::GEN_UNAUTHORIZED->value;

        return $this->respondWithError($message, Response::HTTP_UNAUTHORIZED, $code);
    }

    /**
     * Generates a Response with a 400 HTTP header and a given message.
     */
    public function errorWrongArgs(string $message = 'Wrong Arguments', int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->respondWithError($message, $status, ErrorCode::GEN_WRONG_ARGS->value);
    }

    /**
     * Responds with an API Resource.
     */
    public function respondWithResource(JsonResource $resource, int $status = Response::HTTP_OK, string $message = 'Success.'): JsonResponse
    {
        return response()->json(['message' => $message, 'status' => $status, 'data' => $resource], $status);
    }

    /**
     * Responds with an API Resource.
     */
    public function respondWithArray(?array $data, int $status = Response::HTTP_OK, string $message = 'Success.'): JsonResponse
    {
        return response()->json(['message' => $message, 'status' => $status, 'data' => $data], $status);
    }

    /**
     * Responds with a paginated API Resource.
     */
    public function respondWithPagination(JsonResource $resource, int $status = Response::HTTP_OK, string $message = 'Success.'): JsonResponse
    {
        return response()->json(['status' => $status, 'message' => $message, ...$resource->response()->getData(true)], $status);
    }

    /**
     * Responds with a simple message.
     */
    public function respondWithMessage(string $message, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json(['data' => ['message' => $message]], $status);
    }

    /**
     * When things happens and we don't know why.
     */
    public function respondWithError(string $message, int $status, string $code): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'error' => ['code' => $code, 'status' => $status],
        ], $status);
    }

    /**
     * Resolve the amount of rows to be fetched for pagination purposes.
     */
    public function take(?int $count = null, string $key = 'per_page'): int
    {
        $count ??= config('common.pagination.default');

        return min(request()->query($key, $count), config('common.pagination.maximum'));
    }
}
