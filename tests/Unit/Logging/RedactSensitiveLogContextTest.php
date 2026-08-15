<?php

use App\Logging\RedactSensitiveLogContext;
use Monolog\Level;
use Monolog\LogRecord;

test('sensitive context keys are redacted while safe correlation fields remain available', function () {
    $record = new LogRecord(
        new DateTimeImmutable,
        'structured',
        Level::Info,
        'booking.operation.completed',
        [
            'request_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            'booking_id' => 'booking_123',
            'authorization' => 'Bearer sensitive-token',
            'payment' => [
                'api_key' => 'secret-key',
                'amount_minor' => 2500,
            ],
        ],
        [
            'otp' => '123456',
        ],
    );

    $redacted = (new RedactSensitiveLogContext)($record);

    expect($redacted->context)->toBe([
        'request_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        'booking_id' => 'booking_123',
        'authorization' => '[REDACTED]',
        'payment' => [
            'api_key' => '[REDACTED]',
            'amount_minor' => 2500,
        ],
    ]);
    expect($redacted->extra)->toBe(['otp' => '[REDACTED]']);
});
