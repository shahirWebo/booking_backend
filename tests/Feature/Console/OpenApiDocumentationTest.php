<?php

use App\Support\OpenApiDocument;

test('the committed OpenAPI artifact is generated from the current contract', function () {
    $this->artisan('openapi:generate --check')
        ->expectsOutputToContain('The committed OpenAPI document is current.')
        ->assertSuccessful();
});

test('the OpenAPI contract describes the current platform endpoints and shared errors', function () {
    $document = OpenApiDocument::toArray();

    expect($document['openapi'])->toBe('3.1.1')
        ->and($document['paths']['/api/v1/auth/otp-requests']['post']['operationId'])->toBe('requestOtp')
        ->and($document['paths']['/api/v1/auth/otp-requests']['post']['requestBody']['required'])->toBeTrue()
        ->and($document['paths']['/api/v1/auth/otp-requests']['post']['responses'])->toHaveKey('422')
        ->and($document['paths']['/api/v1/auth/otp-requests']['post']['responses'])->toHaveKey('503')
        ->and($document['paths']['/api/v1']['get']['operationId'])->toBe('getApiV1Root')
        ->and($document['paths']['/api/v1']['get']['responses'])->toHaveKeys(['204', '429'])
        ->and($document['paths']['/health']['get']['operationId'])->toBe('getHealth')
        ->and($document['components']['schemas'])->toHaveKeys([
            'RequestId',
            'ResponseMeta',
            'ErrorResponse',
            'HealthStatus',
        ])
        ->and($document['components']['responses'])->toHaveKey('ServiceUnavailableError');
});
