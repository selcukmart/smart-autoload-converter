<?php

declare(strict_types=1);

namespace App\Infrastructure\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Structured logger for the conversion process.
 * Writes to file and optionally to console output.
 */
class ConversionLogger extends AbstractLogger
{
    private array $entries = [];

    public function __construct(
        private readonly ?string $logFile = null,
    ) {}

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];

        $this->entries[] = $entry;

        if ($this->logFile !== null) {
            $line = sprintf("[%s] %s: %s\n", $entry['timestamp'], strtoupper((string) $level), $entry['message']);
            file_put_contents($this->logFile, $line, FILE_APPEND);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /** @return array<int, array<string, mixed>> */
    public function getErrors(): array
    {
        return array_filter($this->entries, fn(array $e) => $e['level'] === LogLevel::ERROR);
    }
}
