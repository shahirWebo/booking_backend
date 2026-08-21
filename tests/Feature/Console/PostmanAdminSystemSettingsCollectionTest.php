<?php

test('the admin system settings Postman collection covers every endpoint and request variant', function () {
    $collection = json_decode(
        (string) file_get_contents(base_path('../docs/api/postman/Turf-Booking-Admin-System-Settings.postman_collection.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if (! is_array($collection) || ! is_array($collection['item'] ?? null)) {
        throw new RuntimeException('The admin system settings Postman collection must contain request items.');
    }

    $items = collect($collection['item'])->keyBy('name');

    expect($items->keys()->all())->toBe([
        'Show System Settings',
        'Show System Settings - Missing Token',
        'Update System Settings',
        'Update System Settings - Invalid Payload',
    ]);

    expect(data_get($items->get('Show System Settings'), 'request.method'))->toBe('GET')
        ->and(data_get($items->get('Show System Settings'), 'request.url'))->toBe('{{baseUrl}}/admin/system-settings')
        ->and(data_get($items->get('Update System Settings'), 'request.method'))->toBe('PUT')
        ->and(data_get($items->get('Update System Settings'), 'request.url'))->toBe('{{baseUrl}}/admin/system-settings')
        ->and(data_get($items->get('Show System Settings - Missing Token'), 'request.auth.type'))->toBe('noauth');

    $variables = collect(is_array($collection['variable'] ?? null) ? $collection['variable'] : [])
        ->keyBy('key');

    expect(data_get($variables->get('accessToken'), 'value'))->toBe('');
});
