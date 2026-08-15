<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    Route::prefix('api/v1/testing')->group(function (): void {
        Route::get('validation', function (): void {
            throw ValidationException::withMessages([
                'mobile' => ['Enter a valid mobile number.'],
            ]);
        });

        Route::get('unauthenticated', function (): void {
            throw new AuthenticationException;
        });

        Route::get('forbidden', function (): void {
            throw new AuthorizationException;
        });

        Route::get('missing', function (): void {
            throw new NotFoundHttpException;
        });

        Route::get('unexpected', function (): void {
            throw new RuntimeException('A sensitive implementation detail.');
        });
    });
});

test('validation exceptions use the API error contract', function () {
    $this->getJson('/api/v1/testing/validation')
        ->assertUnprocessable()
        ->assertJson([
            'success' => false,
            'code' => 'VALIDATION_ERROR',
            'message' => 'The request contains invalid fields.',
            'errors' => [
                'mobile' => ['Enter a valid mobile number.'],
            ],
            'meta' => [],
        ]);
});

test('authentication, authorization, and not-found exceptions use stable error codes', function (
    string $path,
    int $status,
    string $code,
    string $message,
) {
    $this->getJson($path)
        ->assertStatus($status)
        ->assertJson([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'meta' => [],
        ]);
})->with([
    ['/api/v1/testing/unauthenticated', 401, 'UNAUTHENTICATED', 'Authentication is required.'],
    ['/api/v1/testing/forbidden', 403, 'FORBIDDEN', 'You do not have permission to perform this action.'],
    ['/api/v1/testing/missing', 404, 'RESOURCE_NOT_FOUND', 'The requested resource was not found.'],
]);

test('method-not-allowed responses retain the allowed-method header', function () {
    $this->postJson('/api/v1')
        ->assertMethodNotAllowed()
        ->assertHeader('Allow', 'GET, HEAD')
        ->assertJson([
            'success' => false,
            'code' => 'METHOD_NOT_ALLOWED',
            'message' => 'The requested method is not allowed.',
            'meta' => [],
        ]);
});

test('API path errors render JSON even without an accept header', function () {
    $this->get('/api/v1/testing/not-registered')
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJson([
            'success' => false,
            'code' => 'RESOURCE_NOT_FOUND',
            'message' => 'The requested resource was not found.',
            'meta' => [],
        ]);
});

test('unexpected API exceptions are generic and do not leak implementation details', function () {
    $this->getJson('/api/v1/testing/unexpected')
        ->assertInternalServerError()
        ->assertJson([
            'success' => false,
            'code' => 'INTERNAL_ERROR',
            'message' => 'An unexpected error occurred.',
            'meta' => [],
        ])
        ->assertDontSee('sensitive implementation detail');
});
