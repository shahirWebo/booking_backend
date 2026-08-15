<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ApiExceptionResponse
{
    /**
     * Convert an exception raised while handling a JSON request into the API error shape.
     */
    public static function from(Throwable $exception): JsonResponse
    {
        [$status, $code, $message] = match (true) {
            $exception instanceof ValidationException => [
                422,
                'VALIDATION_ERROR',
                'The request contains invalid fields.',
            ],
            $exception instanceof AuthenticationException => [
                401,
                'UNAUTHENTICATED',
                'Authentication is required.',
            ],
            $exception instanceof AuthorizationException => [
                403,
                'FORBIDDEN',
                'You do not have permission to perform this action.',
            ],
            $exception instanceof MethodNotAllowedHttpException => [
                405,
                'METHOD_NOT_ALLOWED',
                'The requested method is not allowed.',
            ],
            $exception instanceof NotFoundHttpException => [
                404,
                'RESOURCE_NOT_FOUND',
                'The requested resource was not found.',
            ],
            $exception instanceof HttpExceptionInterface => self::httpExceptionDetails($exception),
            default => [
                500,
                'INTERNAL_ERROR',
                'An unexpected error occurred.',
            ],
        };

        $payload = [
            'success' => false,
            'code' => $code,
            'message' => $message,
            'meta' => (object) [],
        ];

        if ($exception instanceof ValidationException) {
            $payload['errors'] = $exception->errors();
        }

        $response = response()->json($payload, $status);

        if ($exception instanceof HttpExceptionInterface) {
            foreach (['Allow', 'Retry-After', 'WWW-Authenticate'] as $header) {
                if (array_key_exists($header, $exception->getHeaders())) {
                    $response->headers->set($header, $exception->getHeaders()[$header]);
                }
            }
        }

        return $response;
    }

    /**
     * @return array{int, string, string}
     */
    private static function httpExceptionDetails(HttpExceptionInterface $exception): array
    {
        return match ($exception->getStatusCode()) {
            400 => [400, 'MALFORMED_REQUEST', 'The request could not be processed.'],
            401 => [401, 'UNAUTHENTICATED', 'Authentication is required.'],
            403 => [403, 'FORBIDDEN', 'You do not have permission to perform this action.'],
            404 => [404, 'RESOURCE_NOT_FOUND', 'The requested resource was not found.'],
            405 => [405, 'METHOD_NOT_ALLOWED', 'The requested method is not allowed.'],
            409 => [409, 'RESOURCE_CONFLICT', 'The request conflicts with the current resource state.'],
            422 => [422, 'VALIDATION_ERROR', 'The request contains invalid fields.'],
            429 => [429, 'RATE_LIMITED', 'Too many requests. Please try again later.'],
            503 => [503, 'SERVICE_UNAVAILABLE', 'The service is temporarily unavailable.'],
            default => [500, 'INTERNAL_ERROR', 'An unexpected error occurred.'],
        };
    }
}
