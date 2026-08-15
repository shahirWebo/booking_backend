<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final class RedactSensitiveLogContext implements ProcessorInterface
{
    private const REDACTED_VALUE = '[REDACTED]';

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->redact($record->context),
            extra: $this->redact($record->extra),
        );
    }

    /**
     * @param  array<mixed>  $context
     * @return array<mixed>
     */
    private function redact(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $context[$key] = self::REDACTED_VALUE;

                continue;
            }

            if (is_array($value)) {
                $context[$key] = $this->redact($value);
            }
        }

        return $context;
    }

    private function isSensitiveKey(string $key): bool
    {
        return preg_match(
            '/(?:^|[_\-.])(?:authorization|cookie|password|secret|token|otp|signature|api[_-]?key|private[_-]?key|access[_-]?key|credential|card|cvv|pan|phone|email|body|payload)(?:$|[_\-.])/i',
            $key,
        ) === 1;
    }
}
