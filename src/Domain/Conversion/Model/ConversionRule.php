<?php

declare(strict_types=1);

namespace App\Domain\Conversion\Model;

readonly class ConversionRule
{
    public function __construct(
        public string $oldClassName,
        public string $newClassName,
        public string $newNamespace,
        public string $newFullyQualifiedName,
        public string $oldFilePath,
        public string $newFilePath,
    ) {}
}
