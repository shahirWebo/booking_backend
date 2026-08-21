<?php

test('the public sports Postman collection covers the active sport list endpoint', function () {
    $collection = json_decode(
        (string) file_get_contents(base_path('../docs/api/postman/Turf-Booking-Public-Sports.postman_collection.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if (! is_array($collection) || ! is_array($collection['item'] ?? null)) {
        throw new RuntimeException('The public sports Postman collection must contain request items.');
    }

    $items = collect($collection['item'])->keyBy('name');

    expect($items->keys()->all())->toBe([
        'List Public Sports',
    ]);

    expect(data_get($items->get('List Public Sports'), 'request.method'))->toBe('GET')
        ->and(data_get($items->get('List Public Sports'), 'request.url'))->toBe('{{baseUrl}}/sports');
});
