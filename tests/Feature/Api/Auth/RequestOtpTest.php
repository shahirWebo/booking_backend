<?php

use App\Models\OtpRequest;

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

    expect(OtpRequest::query()->count())->toBe(0);
});

test('the OTP request endpoint normalizes a supported mobile number before its remaining controls fail closed', function () {
    $this->postJson(route('api.v1.auth.otp_requests.store'), [
        'mobile' => '98765 43210',
    ])
        ->assertServiceUnavailable();
});

test('the OTP request endpoint rejects malformed or unsupported mobile numbers', function (string $mobile) {
    $this->postJson(route('api.v1.auth.otp_requests.store'), [
        'mobile' => $mobile,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonPath('errors.mobile.0', 'Enter a valid mobile number.');
})->with([
    'malformed' => ['not-a-mobile-number'],
    'unsupported region' => ['+14155552671'],
]);

test('the OTP request endpoint does not accept other HTTP methods', function () {
    $this->getJson(route('api.v1.auth.otp_requests.store'))
        ->assertMethodNotAllowed();
});
