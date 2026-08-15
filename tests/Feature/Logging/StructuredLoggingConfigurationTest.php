<?php

use App\Logging\RedactSensitiveLogContext;
use Illuminate\Support\Facades\Log;
use Monolog\Formatter\JsonFormatter;

test('the structured channel uses JSON formatting and context redaction', function () {
    expect(config('logging.channels.structured.formatter'))->toBe(JsonFormatter::class);
    expect(config('logging.channels.structured.processors'))->toContain(RedactSensitiveLogContext::class);

    $logger = Log::channel('structured')->getLogger();

    expect($logger->getHandlers()[0]->getFormatter())->toBeInstanceOf(JsonFormatter::class);
    expect(array_filter(
        $logger->getProcessors(),
        fn (mixed $processor): bool => $processor instanceof RedactSensitiveLogContext,
    ))->not->toBeEmpty();
});
