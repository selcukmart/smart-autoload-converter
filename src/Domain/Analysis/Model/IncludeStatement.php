<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Analysis\Model;

readonly class IncludeStatement
{
    public function __construct(
        public string $filePath,
        public int $lineNumber,
        public IncludeType $type,
        public string $includedPath,
        public bool $isClassFile = false,
        public bool $isDynamic = false,
    ) {}
}
