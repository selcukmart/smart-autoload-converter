<?php

declare(strict_types=1);

namespace App\Domain\FileSystem\Model;

readonly class ScannedFile
{
    public function __construct(
        public string $path,
        public string $relativePath,
        public int $size,
        public string $hash,
    ) {}
}
