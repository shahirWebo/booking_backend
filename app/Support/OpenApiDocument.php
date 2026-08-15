<?php

namespace App\Support;

use JsonException;

final class OpenApiDocument
{
    /**
     * Return the current public HTTP contract as an OpenAPI 3.1 document.
     *
     * @return array<string, mixed>
     */
    public static function toArray(): array
    {
        return [
            'openapi' => '3.1.1',
            'info' => [
                'title' => 'Turf Booking API',
                'version' => '0.1.0',
                'description' => 'The versioned public HTTP contract for the Turf Booking platform.',
            ],
            'tags' => [
                [
                    'name' => 'Platform',
                    'description' => 'Platform-level API and liveness endpoints.',
                ],
            ],
            'paths' => [
                '/api/v1' => [
                    'get' => [
                        'tags' => ['Platform'],
                        'operationId' => 'getApiV1Root',
                        'summary' => 'Check the version-one API route boundary.',
                        'responses' => [
                            '204' => [
                                'description' => 'The version-one API route boundary is available.',
                                'headers' => [
                                    'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                                    'X-RateLimit-Limit' => ['$ref' => '#/components/headers/XRateLimitLimit'],
                                    'X-RateLimit-Remaining' => ['$ref' => '#/components/headers/XRateLimitRemaining'],
                                ],
                            ],
                            '429' => ['$ref' => '#/components/responses/RateLimitedError'],
                        ],
                    ],
                ],
                '/health' => [
                    'get' => [
                        'tags' => ['Platform'],
                        'operationId' => 'getHealth',
                        'summary' => 'Check Laravel process liveness.',
                        'description' => 'This is a liveness endpoint only and does not probe downstream dependencies.',
                        'responses' => [
                            '200' => [
                                'description' => 'The application process can receive and route requests.',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/HealthStatus'],
                                        'example' => ['status' => 'up'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'headers' => [
                    'XRequestId' => [
                        'description' => 'The accepted or server-generated ULID request correlation ID.',
                        'schema' => ['$ref' => '#/components/schemas/RequestId'],
                    ],
                    'XRateLimitLimit' => [
                        'description' => 'The request limit for the active rate-limit window.',
                        'schema' => ['type' => 'integer', 'minimum' => 0],
                    ],
                    'XRateLimitRemaining' => [
                        'description' => 'The requests remaining in the active rate-limit window.',
                        'schema' => ['type' => 'integer', 'minimum' => 0],
                    ],
                    'RetryAfter' => [
                        'description' => 'Seconds until a safe retry when supplied.',
                        'schema' => ['type' => 'integer', 'minimum' => 0],
                    ],
                ],
                'responses' => [
                    'RateLimitedError' => [
                        'description' => 'The caller exceeded the configured rate limit.',
                        'headers' => [
                            'Retry-After' => ['$ref' => '#/components/headers/RetryAfter'],
                            'X-RateLimit-Limit' => ['$ref' => '#/components/headers/XRateLimitLimit'],
                            'X-RateLimit-Remaining' => ['$ref' => '#/components/headers/XRateLimitRemaining'],
                            'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                        ],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                'example' => [
                                    'success' => false,
                                    'code' => 'RATE_LIMITED',
                                    'message' => 'Too many requests. Please try again later.',
                                    'meta' => ['request_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV'],
                                ],
                            ],
                        ],
                    ],
                ],
                'schemas' => [
                    'RequestId' => [
                        'type' => 'string',
                        'pattern' => '^[0-7][0-9A-HJKMNP-TV-Z]{25}$',
                    ],
                    'ResponseMeta' => [
                        'type' => 'object',
                        'required' => ['request_id'],
                        'properties' => [
                            'request_id' => ['$ref' => '#/components/schemas/RequestId'],
                        ],
                    ],
                    'ErrorResponse' => [
                        'type' => 'object',
                        'required' => ['success', 'code', 'message', 'meta'],
                        'properties' => [
                            'success' => ['type' => 'boolean', 'const' => false],
                            'code' => ['type' => 'string'],
                            'message' => ['type' => 'string'],
                            'errors' => [
                                'type' => 'object',
                                'additionalProperties' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                            'meta' => ['$ref' => '#/components/schemas/ResponseMeta'],
                        ],
                    ],
                    'HealthStatus' => [
                        'type' => 'object',
                        'required' => ['status'],
                        'properties' => [
                            'status' => ['type' => 'string', 'const' => 'up'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws JsonException
     */
    public static function toJson(): string
    {
        return json_encode(
            self::toArray(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ).PHP_EOL;
    }

    public static function outputPath(): string
    {
        return base_path('../docs/api/openapi.json');
    }
}
