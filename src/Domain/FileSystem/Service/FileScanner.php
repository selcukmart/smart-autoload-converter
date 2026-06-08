<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\FileSystem\Service;

use SmartAutoloadConverter\Domain\FileSystem\Model\ScannedFile;
use Symfony\Component\Finder\Finder;

/**
 * Recursively scans directories for PHP files, respecting ignore patterns.
 */
class FileScanner
{
    /**
     * Scan a directory for PHP files.
     *
     * @param string $path Directory to scan
     * @param string[] $ignorePatterns Patterns to exclude
     * @param string[] $ignoreFiles Specific files to exclude
     * @return ScannedFile[]
     */
    public function scan(string $path, array $ignorePatterns = [], array $ignoreFiles = []): array
    {
        $finder = new Finder();
        $finder->files()
            ->in($path)
            ->name('*.php')
            ->sortByName();

        foreach ($ignorePatterns as $pattern) {
            $finder->notPath($pattern);
        }

        $files = [];
        foreach ($finder as $file) {
            if (in_array($file->getRelativePathname(), $ignoreFiles, true)) {
                continue;
            }

            $files[] = new ScannedFile(
                path: $file->getRealPath(),
                relativePath: $file->getRelativePathname(),
                size: $file->getSize(),
                hash: md5_file($file->getRealPath()),
            );
        }

        return $files;
    }
}
