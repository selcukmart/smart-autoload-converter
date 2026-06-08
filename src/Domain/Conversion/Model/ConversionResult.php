<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Conversion\Model;

/**
 * Value Object: Result of a conversion operation on a single file.
 */
readonly class ConversionResult
{
    /**
     * @param array<string, string> $replacements old => new mappings applied
     */
    public function __construct(
        public string $filePath,
        public string $originalContent,
        public string $modifiedContent,
        public array $replacements = [],
        public int $changeCount = 0,
        public bool $requiresManualReview = false,
        public ?string $reviewReason = null,
    ) {}

    public function hasChanges(): bool
    {
        return $this->changeCount > 0;
    }
}
