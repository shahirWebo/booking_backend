<?php

use Illuminate\Support\Facades\Route;

test('the version one API root is available through the API route group', function () {
    $this->get('/api/v1')->assertNoContent();

    expect(Route::has('api.v1.index'))->toBeTrue();
});
