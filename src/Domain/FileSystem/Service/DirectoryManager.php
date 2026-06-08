<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\FileSystem\Service;

use SmartAutoloadConverter\Domain\FileSystem\Exception\FileSystemException;

/**
 * Manages directory operations: create, move, copy, delete.
 */
class DirectoryManager
{
    /**
     * Ensure a directory exists, create recursively if needed.
     *
     * @throws FileSystemException
     */
    public function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new FileSystemException("Failed to create directory: {$path}");
        }
    }

    /**
     * Move a file to a new location, creating target dirs as needed.
     */
    public function moveFile(string $source, string $target): void
    {
        $this->ensureDirectory(dirname($target));

        if (!rename($source, $target)) {
            throw new FileSystemException("Failed to move {$source} to {$target}");
        }
    }

    /**
     * Copy entire directory tree recursively.
     */
    public function copyDirectory(string $source, string $target): int
    {
        $this->ensureDirectory($target);
        $count = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $targetPath = $target . DIRECTORY_SEPARATOR . $iterator->getSubPathname();

            if ($item->isDir()) {
                $this->ensureDirectory($targetPath);
            } else {
                copy($item->getRealPath(), $targetPath);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Remove a directory and all its contents recursively.
     */
    public function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = array_diff(scandir($path, SCANDIR_SORT_NONE), ['..', '.']);

        foreach ($items as $item) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $this->removeDirectory($fullPath);
            } else {
                unlink($fullPath);
            }
        }

        rmdir($path);
    }
}
