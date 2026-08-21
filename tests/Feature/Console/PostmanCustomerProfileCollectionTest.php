<?php

test('the customer profile Postman collection covers the get endpoint and auth failure example', function () {
    $collection = json_decode(
        (string) file_get_contents(base_path('../docs/api/postman/Turf-Booking-Customer-Profile.postman_collection.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if (! is_array($collection) || ! is_array($collection['item'] ?? null)) {
        throw new RuntimeException('The customer profile Postman collection must contain request items.');
    }

    $items = collect($collection['item'])->keyBy('name');

    expect($items->keys()->all())->toBe([
        'Get Customer Profile',
        'Get Customer Profile - Missing Token',
    ]);

    expect(data_get($items->get('Get Customer Profile'), 'request.method'))->toBe('GET')
        ->and(data_get($items->get('Get Customer Profile'), 'request.url'))->toBe('{{baseUrl}}/customer/profile')
        ->and(data_get($items->get('Get Customer Profile - Missing Token'), 'request.auth.type'))->toBe('noauth');
});
