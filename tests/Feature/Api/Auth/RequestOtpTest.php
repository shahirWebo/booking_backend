<?php

test('the OTP request endpoint is publicly reachable but fails closed before challenge issuance is implemented', function () {
    $this->postJson(route('api.v1.auth.otp_requests.store'), [
        'mobile' => '+919876543210',
    ])
        ->assertServiceUnavailable()
        ->assertJson([
            'success' => false,
            'code' => 'SERVICE_UNAVAILABLE',
            'message' => 'The service is temporarily unavailable.',
            'meta' => [],
        ]);
});

test('the OTP request endpoint does not accept other HTTP methods', function () {
    $this->getJson(route('api.v1.auth.otp_requests.store'))
        ->assertMethodNotAllowed();
});
