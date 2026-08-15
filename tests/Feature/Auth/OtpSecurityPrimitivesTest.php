<?php

use App\Domain\Auth\Services\MobileNumberNormalizer;
use App\Domain\Auth\Services\OtpCodeGenerator;
use App\Domain\Auth\Services\OtpCodeHasher;
use Illuminate\Support\Facades\Hash;

test('mobile numbers are normalized to E.164 only for permitted regions', function () {
    $normalizer = app(MobileNumberNormalizer::class);

    expect($normalizer->normalize('98765 43210'))->toBe('+919876543210')
        ->and($normalizer->normalize('+919876543210'))->toBe('+919876543210');
});

test('OTP codes are six cryptographically random decimal digits', function () {
    $code = app(OtpCodeGenerator::class)->generate();

    expect($code)->toMatch('/^\d{6}$/');
});

test('OTP hashes are slow, peppered, and bound to their challenge', function () {
    $hasher = app(OtpCodeHasher::class);
    $requestId = '01K2ABCDEF0123456789XYZ';
    $code = '012345';

    $hash = $hasher->hash($requestId, $code);

    expect($hash)->not->toBe($code)
        ->and(Hash::check($code, $hash))->toBeFalse()
        ->and($hasher->verify($requestId, $code, $hash))->toBeTrue()
        ->and($hasher->verify('01K2ABCDEF0123456789XYA', $code, $hash))->toBeFalse()
        ->and($hasher->verify($requestId, '012346', $hash))->toBeFalse();
});

test('OTP hashing rejects malformed codes and a missing pepper', function () {
    $hasher = app(OtpCodeHasher::class);

    expect(fn () => $hasher->hash('01K2ABCDEF0123456789XYZ', '12345'))
        ->toThrow(LogicException::class);

    config()->set('otp.hash_pepper', null);

    expect(fn () => $hasher->hash('01K2ABCDEF0123456789XYZ', '012345'))
        ->toThrow(LogicException::class);
});
