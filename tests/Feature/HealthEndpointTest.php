<?php

test('the liveness endpoint is publicly available without application dependencies', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertExactJson([
            'status' => 'up',
        ]);
});

test('the old starter health path is not exposed', function () {
    $this->get('/up')->assertNotFound();
});
