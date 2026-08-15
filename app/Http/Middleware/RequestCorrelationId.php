<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestCorrelationId
{
    private const HEADER = 'X-Request-ID';

    private const ATTRIBUTE = 'request_id';

    /**
     * Handle API and JSON requests with a safe, server-accepted correlation ID.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isCorrelatedRequest($request)) {
            return $next($request);
        }

        $requestId = $this->resolveRequestId($request);

        $request->attributes->set(self::ATTRIBUTE, $requestId);
        Log::withContext([self::ATTRIBUTE => $requestId]);

        try {
            return $this->attachToResponse($next($request), $requestId);
        } finally {
            Log::withoutContext([self::ATTRIBUTE]);
        }
    }

    private function isCorrelatedRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    private function resolveRequestId(Request $request): string
    {
        $requestId = $request->header(self::HEADER);

        return is_string($requestId) && $this->isValidRequestId($requestId)
            ? $requestId
            : (string) Str::ulid();
    }

    private function isValidRequestId(string $requestId): bool
    {
        return preg_match('/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/', $requestId) === 1;
    }

    private function attachToResponse(Response $response, string $requestId): Response
    {
        $response->headers->set(self::HEADER, $requestId);

        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true);

            if (is_array($payload) && array_key_exists('meta', $payload) && is_array($payload['meta'])) {
                $payload['meta'][self::ATTRIBUTE] = $requestId;
                $response->setData($payload);
            }
        }

        return $response;
    }
}
