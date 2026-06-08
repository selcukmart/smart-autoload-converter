<?php

declare(strict_types=1);

namespace App\Domain\FileSystem\Service;

use App\Domain\FileSystem\Exception\FileSystemException;

/**
 * Handles writing file content with atomic write support.
 * Writes to a temp file first, then moves it to the target (prevents corruption).
 */
class FileWriter
{
    /**
     * Write content to a file atomically.
     *
     * @throws FileSystemException
     */
    public function write(string $filePath, string $content): void
    {
        $dir = dirname($filePath);
        $this->ensureDirectory($dir);

        $tempFile = $filePath . '.tmp.' . uniqid('', true);

        if (file_put_contents($tempFile, $content) === false) {
            throw new FileSystemException("Failed to write temp file: {$tempFile}");
        }

        if (!rename($tempFile, $filePath)) {
            @unlink($tempFile);
            throw new FileSystemException("Failed to move temp file to: {$filePath}");
        }
    }

    /**
     * Read file content.
     *
     * @throws FileSystemException
     */
    public function read(string $filePath): string
    {
        if (!is_file($filePath)) {
            throw new FileSystemException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new FileSystemException("Failed to read file: {$filePath}");
        }

        return $content;
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new FileSystemException("Failed to create directory: {$dir}");
        }
    }
}
