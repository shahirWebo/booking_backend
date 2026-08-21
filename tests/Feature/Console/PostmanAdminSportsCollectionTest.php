<?php

test('the admin sports Postman collection covers every endpoint and request variant', function () {
    $collection = json_decode(
        (string) file_get_contents(base_path('../docs/api/postman/Turf-Booking-Admin-Sports.postman_collection.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if (! is_array($collection) || ! is_array($collection['item'] ?? null)) {
        throw new RuntimeException('The admin sports Postman collection must contain request items.');
    }

    $items = collect($collection['item'])->keyBy('name');

    expect($items->keys()->all())->toBe([
        'List Sports',
        'List Sports - Missing Token',
        'Create Sport',
        'Create Sport - Invalid Payload',
        'Show Sport',
        'Update Sport',
        'Delete Sport',
    ]);

    expect(data_get($items->get('List Sports'), 'request.method'))->toBe('GET')
        ->and(data_get($items->get('List Sports'), 'request.url'))->toBe('{{baseUrl}}/admin/sports')
        ->and(data_get($items->get('Create Sport'), 'request.method'))->toBe('POST')
        ->and(data_get($items->get('Create Sport'), 'request.url'))->toBe('{{baseUrl}}/admin/sports')
        ->and(data_get($items->get('Show Sport'), 'request.method'))->toBe('GET')
        ->and(data_get($items->get('Show Sport'), 'request.url'))->toBe('{{baseUrl}}/admin/sports/{{sportId}}')
        ->and(data_get($items->get('Update Sport'), 'request.method'))->toBe('PUT')
        ->and(data_get($items->get('Delete Sport'), 'request.method'))->toBe('DELETE')
        ->and(data_get($items->get('List Sports - Missing Token'), 'request.auth.type'))->toBe('noauth');

    $variables = collect(is_array($collection['variable'] ?? null) ? $collection['variable'] : [])
        ->keyBy('key');

    expect(data_get($variables->get('sportId'), 'value'))->toBe('')
        ->and(data_get($variables->get('accessToken'), 'value'))->toBe('');

    $createScript = implode("\n", (array) data_get($items->get('Create Sport'), 'event.0.script.exec', []));
    $deleteScript = implode("\n", (array) data_get($items->get('Delete Sport'), 'event.0.script.exec', []));

    expect($createScript)->toContain("pm.collectionVariables.set('sportId'")
        ->and($deleteScript)->toContain("pm.collectionVariables.unset('sportId'");
});
