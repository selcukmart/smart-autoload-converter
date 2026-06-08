<?php

declare(strict_types=1);

namespace App\Domain\Conversion\Model;

/**
 * Value Object: Maps a source directory to a PSR-4 namespace root.
 * Example: directory "include/MyLib" => namespace "MyLib"
 */
readonly class NamespaceMapping
{
    public function __construct(
        public string $directory,
        public string $namespaceRoot,
    ) {}

    /**
     * Check if a file path falls under this mapping.
     */
    public function matches(string $filePath): bool
    {
        return str_starts_with($filePath, $this->directory);
    }

    /**
     * Get the relative path after the mapped directory.
     */
    public function getRelativePath(string $filePath): string
    {
        return ltrim(substr($filePath, strlen($this->directory)), '/\\');
    }
}
