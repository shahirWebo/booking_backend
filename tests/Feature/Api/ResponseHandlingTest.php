<?php

use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Api\ResponseTestResource;

beforeEach(function () {
    Route::prefix('api/v1/testing/responses')->group(function (): void {
        Route::get('single', fn () => ApiResponse::success(
            new ResponseTestResource(['id' => 'sport_football', 'name' => 'Football']),
            message: 'Sport loaded.',
        ));

        Route::get('collection', fn () => ApiResponse::collection([
            new ResponseTestResource(['id' => 'sport_football', 'name' => 'Football']),
            new ResponseTestResource(['id' => 'sport_cricket', 'name' => 'Cricket']),
        ]));

        Route::get('created', fn () => ApiResponse::created(
            ['id' => 'hold_123'],
            '/api/v1/slot-holds/hold_123',
            'Slot hold created.',
        ));

        Route::delete('no-content', fn () => ApiResponse::noContent());

        Route::get('page', fn () => ApiResponse::paginated(
            new LengthAwarePaginator(
                [['id' => 'booking_123', 'status' => 'confirmed']],
                3,
                1,
                2,
                ['path' => '/api/v1/customer/bookings'],
            ),
        ));

        Route::get('cursor', fn () => ApiResponse::paginated(
            new CursorPaginator(
                [['id' => 'notification_123']],
                1,
                null,
                ['path' => '/api/v1/customer/notifications'],
            ),
        ));
    });
});

test('a resource is returned once inside the canonical success envelope', function () {
    $this->getJson('/api/v1/testing/responses/single')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Sport loaded.',
            'data' => [
                'id' => 'sport_football',
                'name' => 'Football',
            ],
            'meta' => [],
        ])
        ->assertJsonMissingPath('data.data');
});

test('an unpaginated collection returns an array in data', function () {
    $this->getJson('/api/v1/testing/responses/collection')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.1.id', 'sport_cricket')
        ->assertJsonPath('meta', []);
});

test('created responses include the supplied location', function () {
    $this->getJson('/api/v1/testing/responses/created')
        ->assertCreated()
        ->assertHeader('Location', '/api/v1/slot-holds/hold_123')
        ->assertJson([
            'success' => true,
            'message' => 'Slot hold created.',
            'data' => ['id' => 'hold_123'],
            'meta' => [],
        ]);
});

test('no-content responses have no body', function () {
    $this->deleteJson('/api/v1/testing/responses/no-content')
        ->assertNoContent();
});

test('page-number pagination uses the canonical metadata and links', function () {
    $this->getJson('/api/v1/testing/responses/page')
        ->assertOk()
        ->assertJsonPath('data.0.id', 'booking_123')
        ->assertJsonPath('meta.pagination.current_page', 2)
        ->assertJsonPath('meta.pagination.total', 3)
        ->assertJsonPath('links.first', '/api/v1/customer/bookings?page=1')
        ->assertJsonPath('links.last', '/api/v1/customer/bookings?page=3')
        ->assertJsonPath('links.prev', '/api/v1/customer/bookings?page=1')
        ->assertJsonPath('links.next', '/api/v1/customer/bookings?page=3');
});

test('cursor pagination uses cursor metadata and links', function () {
    $this->getJson('/api/v1/testing/responses/cursor')
        ->assertOk()
        ->assertJsonPath('data.0.id', 'notification_123')
        ->assertJsonPath('meta.pagination.per_page', 1)
        ->assertJsonPath('meta.pagination.has_more', false)
        ->assertJsonPath('links.prev', null)
        ->assertJsonPath('links.next', null);
});

test('Eloquent models cannot bypass an API Resource or explicit projection', function () {
    expect(fn () => ApiResponse::success(User::factory()->make()))
        ->toThrow(LogicException::class);
});
