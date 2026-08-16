<?php

test('the authentication Postman collection covers every endpoint and request variant', function () {
    $collection = json_decode(
        (string) file_get_contents(base_path('../docs/api/postman/Turf-Booking-Authentication.postman_collection.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if (! is_array($collection) || ! is_array($collection['item'] ?? null)) {
        throw new RuntimeException('The authentication Postman collection must contain request items.');
    }

    $items = collect($collection['item'])->keyBy('name');

    expect($items->keys()->all())->toBe([
        'Request OTP',
        'Request OTP - Invalid Mobile',
        'Verify OTP',
        'Verify OTP - Invalid Code',
        'Get Current User',
        'Get Current User - Missing Token',
        'Logout',
        'Logout - Missing Token',
    ]);

    expect(data_get($items->get('Request OTP'), 'request.method'))->toBe('POST')
        ->and(data_get($items->get('Request OTP'), 'request.url'))->toBe('{{baseUrl}}/auth/otp-requests')
        ->and(data_get($items->get('Verify OTP'), 'request.method'))->toBe('POST')
        ->and(data_get($items->get('Verify OTP'), 'request.url'))->toBe('{{baseUrl}}/auth/otp-verifications')
        ->and(data_get($items->get('Get Current User'), 'request.method'))->toBe('GET')
        ->and(data_get($items->get('Get Current User'), 'request.url'))->toBe('{{baseUrl}}/auth/user')
        ->and(data_get($items->get('Logout'), 'request.method'))->toBe('DELETE')
        ->and(data_get($items->get('Logout'), 'request.url'))->toBe('{{baseUrl}}/auth/session')
        ->and(data_get($items->get('Get Current User - Missing Token'), 'request.auth.type'))->toBe('noauth')
        ->and(data_get($items->get('Logout - Missing Token'), 'request.auth.type'))->toBe('noauth');

    $variables = collect(is_array($collection['variable'] ?? null) ? $collection['variable'] : [])
        ->keyBy('key');

    expect(data_get($variables->get('otpRequestId'), 'value'))->toBe('')
        ->and(data_get($variables->get('otpCode'), 'value'))->toBe('')
        ->and(data_get($variables->get('accessToken'), 'value'))->toBe('');

    $requestOtpScript = implode("\n", (array) data_get($items->get('Request OTP'), 'event.0.script.exec', []));
    $verifyOtpScript = implode("\n", (array) data_get($items->get('Verify OTP'), 'event.0.script.exec', []));
    $logoutScript = implode("\n", (array) data_get($items->get('Logout'), 'event.0.script.exec', []));

    expect($requestOtpScript)->toContain("pm.collectionVariables.set('otpRequestId'")
        ->and($verifyOtpScript)->toContain("pm.collectionVariables.set('accessToken'")
        ->and($logoutScript)->toContain("pm.collectionVariables.unset('accessToken'");
});
