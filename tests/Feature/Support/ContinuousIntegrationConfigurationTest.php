<?php

test('the backend CI command verifies the generated contract, PHP quality, and tests', function () {
    $composer = json_decode(
        (string) file_get_contents(base_path('composer.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($composer['scripts']['ci:backend'])->toBe([
        'Composer\\Config::disableProcessTimeout',
        '@openapi:check',
        '@test',
    ])
        ->and($composer['scripts']['ci:check'])->toContain('@ci:backend')
        ->and($composer['scripts']['test'])->toContain('@lint:check')
        ->and($composer['scripts']['test'])->toContain('@types:check')
        ->and($composer['scripts']['test'])->toContain('@php artisan test');
});

test('the GitHub workflow installs lockfile dependencies without local setup side effects', function () {
    $workflow = file_get_contents(base_path('.github/workflows/tests.yml'));

    expect($workflow)->toBeString()
        ->toContain('composer install --no-interaction --prefer-dist --no-progress')
        ->toContain('npm ci')
        ->toContain('composer ci:check')
        ->not->toContain('composer setup');
});
