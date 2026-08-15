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
                    'name' => 'Authentication',
                    'description' => 'Passwordless authentication challenge endpoints.',
                ],
                [
                    'name' => 'Platform',
                    'description' => 'Platform-level API and liveness endpoints.',
                ],
            ],
            'paths' => [
                '/api/v1/auth/otp-requests' => [
                    'post' => [
                        'tags' => ['Authentication'],
                        'operationId' => 'requestOtp',
                        'summary' => 'Request an SMS OTP authentication challenge.',
                        'description' => 'The endpoint accepts and normalizes supported mobile numbers, then currently fails closed until challenge issuance, anti-abuse, and provider-delivery controls are implemented.',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/OtpRequestInput'],
                                ],
                            ],
                        ],
                        'responses' => [
                            '422' => ['$ref' => '#/components/responses/ValidationError'],
                            '503' => ['$ref' => '#/components/responses/ServiceUnavailableError'],
                        ],
                    ],
                ],
                '/api/v1/auth/otp-verifications' => [
                    'post' => [
                        'tags' => ['Authentication'],
                        'operationId' => 'verifyOtp',
                        'summary' => 'Verify an SMS OTP and establish API authentication.',
                        'description' => 'Consumes one valid OTP challenge and returns a bearer token. Invalid, expired, consumed, superseded, and attempt-exhausted challenges have the same response. This response must not be cached.',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/OtpVerificationInput'],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'The OTP was verified and a bearer credential was issued.',
                                'headers' => [
                                    'Cache-Control' => [
                                        'schema' => ['type' => 'string', 'const' => 'no-store, private'],
                                    ],
                                    'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                                ],
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/OtpAuthenticationResponse'],
                                    ],
                                ],
                            ],
                            '422' => ['$ref' => '#/components/responses/ValidationError'],
                            '429' => ['$ref' => '#/components/responses/RateLimitedError'],
                        ],
                    ],
                ],
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
                    'ValidationError' => [
                        'description' => 'The request contains invalid fields.',
                        'headers' => [
                            'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                        ],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/ValidationErrorResponse'],
                                'example' => [
                                    'success' => false,
                                    'code' => 'VALIDATION_ERROR',
                                    'message' => 'The request contains invalid fields.',
                                    'errors' => [
                                        'mobile' => ['Enter a valid mobile number.'],
                                    ],
                                    'meta' => ['request_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV'],
                                ],
                            ],
                        ],
                    ],
                    'ServiceUnavailableError' => [
                        'description' => 'A required service is temporarily unavailable.',
                        'headers' => [
                            'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                        ],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                'example' => [
                                    'success' => false,
                                    'code' => 'SERVICE_UNAVAILABLE',
                                    'message' => 'The service is temporarily unavailable.',
                                    'meta' => ['request_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV'],
                                ],
                            ],
                        ],
                    ],
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
                    'ValidationErrorResponse' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/ErrorResponse'],
                            [
                                'type' => 'object',
                                'required' => ['errors'],
                            ],
                        ],
                    ],
                    'OtpRequestInput' => [
                        'type' => 'object',
                        'required' => ['mobile'],
                        'properties' => [
                            'mobile' => [
                                'type' => 'string',
                                'maxLength' => 64,
                                'description' => 'A supported mobile number. The server normalizes accepted input to E.164 and never returns it.',
                            ],
                        ],
                    ],
                    'OtpVerificationInput' => [
                        'type' => 'object',
                        'required' => ['otp_request_id', 'code'],
                        'properties' => [
                            'otp_request_id' => [
                                'type' => 'string',
                                'description' => 'The opaque OTP request ULID returned when the challenge was accepted.',
                            ],
                            'code' => [
                                'type' => 'string',
                                'pattern' => '^\\d{6}$',
                                'description' => 'The six-digit code received by SMS.',
                            ],
                        ],
                    ],
                    'OtpAuthenticationResponse' => [
                        'type' => 'object',
                        'required' => ['success', 'data', 'meta'],
                        'properties' => [
                            'success' => ['type' => 'boolean', 'const' => true],
                            'data' => [
                                'type' => 'object',
                                'required' => ['access_token', 'token_type'],
                                'properties' => [
                                    'access_token' => [
                                        'type' => 'string',
                                        'description' => 'Bearer credential returned only once. Store it securely and never log it.',
                                    ],
                                    'token_type' => ['type' => 'string', 'const' => 'Bearer'],
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
