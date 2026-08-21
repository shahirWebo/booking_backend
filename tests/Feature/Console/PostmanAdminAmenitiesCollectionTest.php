<?php

test('the admin amenities Postman collection covers every endpoint and request variant', function () {
    $collection = json_decode(
        (string) file_get_contents(base_path('../docs/api/postman/Turf-Booking-Admin-Amenities.postman_collection.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if (! is_array($collection) || ! is_array($collection['item'] ?? null)) {
        throw new RuntimeException('The admin amenities Postman collection must contain request items.');
    }

    $items = collect($collection['item'])->keyBy('name');

    expect($items->keys()->all())->toBe([
        'List Amenities',
        'List Amenities - Missing Token',
        'Create Amenity',
        'Create Amenity - Invalid Payload',
        'Show Amenity',
        'Update Amenity',
        'Delete Amenity',
    ]);

    expect(data_get($items->get('List Amenities'), 'request.method'))->toBe('GET')
        ->and(data_get($items->get('List Amenities'), 'request.url'))->toBe('{{baseUrl}}/admin/amenities')
        ->and(data_get($items->get('Create Amenity'), 'request.method'))->toBe('POST')
        ->and(data_get($items->get('Create Amenity'), 'request.url'))->toBe('{{baseUrl}}/admin/amenities')
        ->and(data_get($items->get('Show Amenity'), 'request.method'))->toBe('GET')
        ->and(data_get($items->get('Show Amenity'), 'request.url'))->toBe('{{baseUrl}}/admin/amenities/{{amenityId}}')
        ->and(data_get($items->get('Update Amenity'), 'request.method'))->toBe('PUT')
        ->and(data_get($items->get('Delete Amenity'), 'request.method'))->toBe('DELETE')
        ->and(data_get($items->get('List Amenities - Missing Token'), 'request.auth.type'))->toBe('noauth');

    $variables = collect(is_array($collection['variable'] ?? null) ? $collection['variable'] : [])
        ->keyBy('key');

    expect(data_get($variables->get('amenityId'), 'value'))->toBe('')
        ->and(data_get($variables->get('accessToken'), 'value'))->toBe('');

    $createScript = implode("\n", (array) data_get($items->get('Create Amenity'), 'event.0.script.exec', []));
    $deleteScript = implode("\n", (array) data_get($items->get('Delete Amenity'), 'event.0.script.exec', []));

    expect($createScript)->toContain("pm.collectionVariables.set('amenityId'")
        ->and($deleteScript)->toContain("pm.collectionVariables.unset('amenityId'");
});
