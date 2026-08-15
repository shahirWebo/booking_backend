<?php

use App\Support\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Queue\QueuePayloadTestJob;

beforeEach(function () {
    Route::get('api/v1/testing/queue-payload', function () {
        Queue::connection('database')->push(new QueuePayloadTestJob);

        return ApiResponse::success(['queued' => true]);
    });
});

test('database queue connections use durable after-commit settings', function () {
    expect(config('queue.connections.database.after_commit'))->toBeTrue();
    expect(config('queue.connections.database.retry_after'))->toBe(90);
    expect(config('queue.connections.database_long.after_commit'))->toBeTrue();
    expect(config('queue.connections.database_long.retry_after'))->toBe(300);
    expect(config('queue.failed.driver'))->toBe('database-uuids');
});

test('accepted request IDs are included in durable queue payloads', function () {
    $requestId = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    $this->withHeader('X-Request-ID', $requestId)
        ->getJson('/api/v1/testing/queue-payload')
        ->assertOk()
        ->assertJsonPath('meta.request_id', $requestId);

    $payload = json_decode((string) DB::table('jobs')->value('payload'), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['request_id'])->toBe($requestId);
});
