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
        ->and($document['paths']['/api/v1/sports']['get']['operationId'])->toBe('listSports')
        ->and($document['paths']['/api/v1/sports']['get']['responses'])->toHaveKeys(['200', '429'])
        ->and($document['paths']['/api/v1/auth/otp-requests']['post']['operationId'])->toBe('requestOtp')
        ->and($document['paths']['/api/v1/auth/otp-requests']['post']['requestBody']['required'])->toBeTrue()
        ->and($document['paths']['/api/v1/auth/otp-requests']['post']['responses'])->toHaveKey('422')
        ->and($document['paths']['/api/v1/auth/otp-requests']['post']['responses'])->toHaveKey('503')
        ->and($document['paths']['/api/v1/auth/otp-requests']['post']['responses'])->toHaveKey('429')
        ->and($document['paths']['/api/v1/auth/otp-verifications']['post']['responses'])->toHaveKeys(['200', '403', '422', '429'])
        ->and($document['paths']['/api/v1/auth/user']['get']['operationId'])->toBe('getCurrentUser')
        ->and($document['paths']['/api/v1/auth/user']['get']['security'])->toBe([['BearerAuth' => []]])
        ->and($document['paths']['/api/v1/auth/user']['get']['responses'])->toHaveKeys(['200', '401', '403', '429'])
        ->and($document['paths']['/api/v1/auth/session']['delete']['operationId'])->toBe('logout')
        ->and($document['paths']['/api/v1/auth/session']['delete']['responses'])->toHaveKeys(['204', '401', '403', '429'])
        ->and($document['paths']['/api/v1/admin/sports']['get']['operationId'])->toBe('listAdminSports')
        ->and($document['paths']['/api/v1/admin/sports']['post']['responses'])->toHaveKeys(['201', '401', '403', '422', '429'])
        ->and($document['paths']['/api/v1/admin/sports/{sport}']['get']['operationId'])->toBe('getAdminSport')
        ->and($document['paths']['/api/v1/admin/sports/{sport}']['put']['responses'])->toHaveKeys(['200', '401', '403', '404', '422', '429'])
        ->and($document['paths']['/api/v1/admin/sports/{sport}']['delete']['responses'])->toHaveKeys(['204', '401', '403', '404', '429'])
        ->and($document['paths']['/api/v1']['get']['operationId'])->toBe('getApiV1Root')
        ->and($document['paths']['/api/v1']['get']['responses'])->toHaveKeys(['204', '429'])
        ->and($document['paths']['/health']['get']['operationId'])->toBe('getHealth')
        ->and($document['components']['schemas'])->toHaveKeys([
            'RequestId',
            'ResponseMeta',
            'ErrorResponse',
            'Sport',
            'SportInput',
            'SportResponse',
            'SportCollectionResponse',
            'PublicSport',
            'PublicSportCollectionResponse',
            'HealthStatus',
        ])
        ->and($document['components']['responses'])->toHaveKeys([
            'ForbiddenError',
            'ResourceNotFoundError',
            'ServiceUnavailableError',
            'UnauthenticatedError',
            'OtpVerificationRejectedError',
            'RestrictedAccountError',
        ])
        ->and($document['components']['securitySchemes'])->toHaveKey('BearerAuth');
});
