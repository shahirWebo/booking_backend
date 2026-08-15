<?php

use App\Support\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

const VALID_REQUEST_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

beforeEach(function () {
    Route::get('api/v1/testing/correlation', fn () => ApiResponse::success(['ok' => true]));
});

test('a valid client request ID is preserved in the header, envelope, and log context', function () {
    Log::spy();

    $this->withHeader('X-Request-ID', VALID_REQUEST_ID)
        ->getJson('/api/v1/testing/correlation')
        ->assertOk()
        ->assertHeader('X-Request-ID', VALID_REQUEST_ID)
        ->assertJsonPath('meta.request_id', VALID_REQUEST_ID);

    Log::shouldHaveReceived('withContext')
        ->once()
        ->with(['request_id' => VALID_REQUEST_ID]);
    Log::shouldHaveReceived('withoutContext')
        ->once()
        ->with(['request_id']);
});

test('missing or invalid request IDs are replaced with generated ULIDs', function (array $headers) {
    $response = $this->withHeaders($headers)->getJson('/api/v1/testing/correlation');
    $requestId = $response->headers->get('X-Request-ID');

    $response
        ->assertOk()
        ->assertJsonPath('meta.request_id', $requestId);

    expect($requestId)
        ->toMatch('/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/')
        ->not->toBe($headers['X-Request-ID'] ?? null);
})->with([
    'missing header' => [[]],
    'lowercase ULID' => [['X-Request-ID' => '01arz3ndektsv4rrffq69g5fav']],
    'malformed header' => [['X-Request-ID' => 'not-a-request-id']],
]);

test('a route-not-found API error receives the same accepted request ID', function () {
    $this->withHeader('X-Request-ID', VALID_REQUEST_ID)
        ->get('/api/v1/testing/not-registered')
        ->assertNotFound()
        ->assertHeader('X-Request-ID', VALID_REQUEST_ID)
        ->assertJsonPath('meta.request_id', VALID_REQUEST_ID);
});

test('no-content API responses still include the request ID header', function () {
    $this->withHeader('X-Request-ID', VALID_REQUEST_ID)
        ->get('/api/v1')
        ->assertNoContent()
        ->assertHeader('X-Request-ID', VALID_REQUEST_ID);
});
