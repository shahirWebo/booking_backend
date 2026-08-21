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
                [
                    'name' => 'Admin Sports',
                    'description' => 'Administrative sport master-data management endpoints.',
                ],
            ],
            'paths' => [
                '/api/v1/auth/otp-requests' => [
                    'post' => [
                        'tags' => ['Authentication'],
                        'operationId' => 'requestOtp',
                        'summary' => 'Request an SMS OTP authentication challenge.',
                        'description' => 'The endpoint accepts and normalizes supported mobile numbers, applies privacy-safe abuse controls, then accepts an opaque OTP challenge for asynchronous delivery. A successful response does not assert that an SMS was delivered and must not be cached.',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/OtpRequestInput'],
                                ],
                            ],
                        ],
                        'responses' => [
                            '202' => [
                                'description' => 'The OTP challenge was accepted for asynchronous delivery.',
                                'headers' => [
                                    'Cache-Control' => [
                                        'schema' => ['type' => 'string', 'const' => 'no-store, private'],
                                    ],
                                    'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                                ],
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/OtpRequestAcceptedResponse'],
                                    ],
                                ],
                            ],
                            '422' => ['$ref' => '#/components/responses/ValidationError'],
                            '503' => ['$ref' => '#/components/responses/ServiceUnavailableError'],
                            '429' => ['$ref' => '#/components/responses/RateLimitedError'],
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
                            '422' => ['$ref' => '#/components/responses/OtpVerificationRejectedError'],
                            '403' => ['$ref' => '#/components/responses/RestrictedAccountError'],
                            '429' => ['$ref' => '#/components/responses/RateLimitedError'],
                        ],
                    ],
                ],
                '/api/v1/auth/user' => [
                    'get' => [
                        'tags' => ['Authentication'],
                        'operationId' => 'getCurrentUser',
                        'summary' => 'Get the current authenticated user.',
                        'description' => 'Returns the public profile for the user represented by the current bearer credential.',
                        'security' => [['BearerAuth' => []]],
                        'responses' => [
                            '200' => [
                                'description' => 'The current authenticated user.',
                                'headers' => [
                                    'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                                ],
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/UserProfileResponse'],
                                    ],
                                ],
                            ],
                            '401' => ['$ref' => '#/components/responses/UnauthenticatedError'],
                            '403' => ['$ref' => '#/components/responses/RestrictedAccountError'],
                            '429' => ['$ref' => '#/components/responses/RateLimitedError'],
                        ],
                    ],
                ],
                '/api/v1/auth/session' => [
                    'delete' => [
                        'tags' => ['Authentication'],
                        'operationId' => 'logout',
                        'summary' => 'Revoke the current bearer credential.',
                        'description' => 'Revokes only the bearer token used for this request. Other active sessions remain authenticated.',
                        'security' => [['BearerAuth' => []]],
                        'responses' => [
                            '204' => [
                                'description' => 'The current bearer credential was revoked.',
                                'headers' => [
                                    'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                                ],
                            ],
                            '401' => ['$ref' => '#/components/responses/UnauthenticatedError'],
                            '403' => ['$ref' => '#/components/responses/RestrictedAccountError'],
                            '429' => ['$ref' => '#/components/responses/RateLimitedError'],
                        ],
                    ],
                ],
                '/api/v1/admin/sports' => [
                    'get' => [
                        'tags' => ['Admin Sports'],
                        'operationId' => 'listAdminSports',
                        'summary' => 'List sports for platform administration.',
                        'security' => [['BearerAuth' => []]],
                        'responses' => [
                            '200' => [
                                'description' => 'The current sports master-data list.',
                                'headers' => [
                                    'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                                ],
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/SportCollectionResponse'],
                                    ],
                                ],
                            ],
                            '401' => ['$ref' => '#/components/responses/UnauthenticatedError'],
                            '403' => ['$ref' => '#/components/responses/ForbiddenError'],
                            '429' => ['$ref' => '#/components/responses/RateLimitedError'],
                        ],
                    ],
                    'post' => [
                        'tags' => ['Admin Sports'],
                        'operationId' => 'createAdminSport',
                        'summary' => 'Create a sport for platform administration.',
                        'security' => [['BearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/SportInput'],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'The sport was created.',
                                'headers' => [
                                    'Location' => [
                                        'schema' => ['type' => 'string'],
                                    ],
                                    'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                                ],
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/SportResponse'],
                                    ],
                                ],
                            ],
                            '401' => ['$ref' => '#/components/responses/UnauthenticatedError'],
                            '403' => ['$ref' => '#/components/responses/ForbiddenError'],
                            '422' => ['$ref' => '#/components/responses/ValidationError'],
                            '429' => ['$ref' => '#/components/responses/RateLimitedError'],
                        ],
                    ],
                ],
                '/api/v1/admin/sports/{sport}' => [
                    'get' => [
                        'tags' => ['Admin Sports'],
                        'operationId' => 'getAdminSport',
                        'summary' => 'Get one sport for platform administration.',
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [
                            [
                                'name' => 'sport',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer', 'minimum' => 1],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'The requested sport.',
                                'headers' => [
                                    'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                                ],
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/SportResponse'],
                                    ],
                                ],
                            ],
                            '401' => ['$ref' => '#/components/responses/UnauthenticatedError'],
                            '403' => ['$ref' => '#/components/responses/ForbiddenError'],
                            '404' => ['$ref' => '#/components/responses/ResourceNotFoundError'],
                            '429' => ['$ref' => '#/components/responses/RateLimitedError'],
                        ],
                    ],
                    'put' => [
                        'tags' => ['Admin Sports'],
                        'operationId' => 'updateAdminSport',
                        'summary' => 'Update one sport for platform administration.',
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [
                            [
                                'name' => 'sport',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer', 'minimum' => 1],
                            ],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/SportInput'],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'The sport was updated.',
                                'headers' => [
                                    'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                                ],
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/SportResponse'],
                                    ],
                                ],
                            ],
                            '401' => ['$ref' => '#/components/responses/UnauthenticatedError'],
                            '403' => ['$ref' => '#/components/responses/ForbiddenError'],
                            '404' => ['$ref' => '#/components/responses/ResourceNotFoundError'],
                            '422' => ['$ref' => '#/components/responses/ValidationError'],
                            '429' => ['$ref' => '#/components/responses/RateLimitedError'],
                        ],
                    ],
                    'delete' => [
                        'tags' => ['Admin Sports'],
                        'operationId' => 'deleteAdminSport',
                        'summary' => 'Delete one sport for platform administration.',
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [
                            [
                                'name' => 'sport',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer', 'minimum' => 1],
                            ],
                        ],
                        'responses' => [
                            '204' => [
                                'description' => 'The sport was deleted.',
                                'headers' => [
                                    'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                                ],
                            ],
                            '401' => ['$ref' => '#/components/responses/UnauthenticatedError'],
                            '403' => ['$ref' => '#/components/responses/ForbiddenError'],
                            '404' => ['$ref' => '#/components/responses/ResourceNotFoundError'],
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
                    'UnauthenticatedError' => [
                        'description' => 'Authentication is required.',
                        'headers' => [
                            'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                        ],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                'example' => [
                                    'success' => false,
                                    'code' => 'UNAUTHENTICATED',
                                    'message' => 'Authentication is required.',
                                    'meta' => ['request_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV'],
                                ],
                            ],
                        ],
                    ],
                    'ForbiddenError' => [
                        'description' => 'The authenticated user does not have permission to perform this action.',
                        'headers' => [
                            'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                        ],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                'example' => [
                                    'success' => false,
                                    'code' => 'FORBIDDEN',
                                    'message' => 'You do not have permission to perform this action.',
                                    'meta' => ['request_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV'],
                                ],
                            ],
                        ],
                    ],
                    'ResourceNotFoundError' => [
                        'description' => 'The requested resource was not found.',
                        'headers' => [
                            'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                        ],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                'example' => [
                                    'success' => false,
                                    'code' => 'RESOURCE_NOT_FOUND',
                                    'message' => 'The requested resource was not found.',
                                    'meta' => ['request_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV'],
                                ],
                            ],
                        ],
                    ],
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
                    'OtpVerificationRejectedError' => [
                        'description' => 'The request fields are invalid, or the OTP challenge is invalid, expired, consumed, superseded, or exhausted. Challenge lifecycle states are deliberately not disclosed.',
                        'headers' => [
                            'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                        ],
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'oneOf' => [
                                        ['$ref' => '#/components/schemas/ValidationErrorResponse'],
                                        ['$ref' => '#/components/schemas/OtpVerificationRejectedErrorResponse'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'RestrictedAccountError' => [
                        'description' => 'The authenticated account is blocked or suspended. The current bearer credential is revoked.',
                        'headers' => [
                            'X-Request-ID' => ['$ref' => '#/components/headers/XRequestId'],
                        ],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/RestrictedAccountErrorResponse'],
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
                    'OtpVerificationRejectedErrorResponse' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/ErrorResponse'],
                            [
                                'type' => 'object',
                                'properties' => [
                                    'code' => ['type' => 'string', 'const' => 'VALIDATION_ERROR'],
                                ],
                            ],
                        ],
                    ],
                    'RestrictedAccountErrorResponse' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/ErrorResponse'],
                            [
                                'type' => 'object',
                                'properties' => [
                                    'code' => [
                                        'type' => 'string',
                                        'enum' => ['USER_BLOCKED', 'USER_SUSPENDED'],
                                    ],
                                ],
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
                    'OtpRequestAcceptedResponse' => [
                        'type' => 'object',
                        'required' => ['success', 'data', 'meta'],
                        'properties' => [
                            'success' => ['type' => 'boolean', 'const' => true],
                            'data' => [
                                'type' => 'object',
                                'required' => ['otp_request_id', 'expires_at', 'resend_available_at'],
                                'properties' => [
                                    'otp_request_id' => ['$ref' => '#/components/schemas/RequestId'],
                                    'expires_at' => ['type' => 'string', 'format' => 'date-time'],
                                    'resend_available_at' => ['type' => 'string', 'format' => 'date-time'],
                                ],
                            ],
                            'meta' => ['$ref' => '#/components/schemas/ResponseMeta'],
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
                    'UserProfileResponse' => [
                        'type' => 'object',
                        'required' => ['success', 'data', 'meta'],
                        'properties' => [
                            'success' => ['type' => 'boolean', 'const' => true],
                            'data' => [
                                'type' => 'object',
                                'required' => ['id', 'name', 'mobile_number', 'email', 'status'],
                                'properties' => [
                                    'id' => ['type' => 'integer', 'minimum' => 1],
                                    'name' => ['type' => ['string', 'null']],
                                    'mobile_number' => ['type' => ['string', 'null'], 'description' => 'The user\'s verified mobile number in E.164 format when present.'],
                                    'email' => ['type' => ['string', 'null'], 'format' => 'email'],
                                    'status' => ['type' => 'string', 'const' => 'active'],
                                ],
                            ],
                            'meta' => ['$ref' => '#/components/schemas/ResponseMeta'],
                        ],
                    ],
                    'Sport' => [
                        'type' => 'object',
                        'required' => ['id', 'name', 'code', 'description', 'created_at', 'updated_at'],
                        'properties' => [
                            'id' => ['type' => 'integer', 'minimum' => 1],
                            'name' => ['type' => 'string', 'maxLength' => 255],
                            'code' => ['type' => 'string', 'maxLength' => 100, 'pattern' => '^[a-z0-9]+(?:_[a-z0-9]+)*$'],
                            'description' => ['type' => ['string', 'null']],
                            'created_at' => ['type' => 'string', 'format' => 'date-time'],
                            'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                    'SportInput' => [
                        'type' => 'object',
                        'required' => ['name', 'code'],
                        'properties' => [
                            'name' => ['type' => 'string', 'maxLength' => 255],
                            'code' => ['type' => 'string', 'maxLength' => 100, 'pattern' => '^[a-z0-9]+(?:_[a-z0-9]+)*$'],
                            'description' => ['type' => ['string', 'null']],
                        ],
                    ],
                    'SportResponse' => [
                        'type' => 'object',
                        'required' => ['success', 'data', 'meta'],
                        'properties' => [
                            'success' => ['type' => 'boolean', 'const' => true],
                            'message' => ['type' => 'string'],
                            'data' => ['$ref' => '#/components/schemas/Sport'],
                            'meta' => ['$ref' => '#/components/schemas/ResponseMeta'],
                        ],
                    ],
                    'SportCollectionResponse' => [
                        'type' => 'object',
                        'required' => ['success', 'data', 'meta'],
                        'properties' => [
                            'success' => ['type' => 'boolean', 'const' => true],
                            'data' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/Sport'],
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
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Sanctum token',
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
