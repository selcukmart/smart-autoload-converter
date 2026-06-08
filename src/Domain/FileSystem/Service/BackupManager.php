<?php

declare(strict_types=1);

namespace App\Domain\FileSystem\Service;

use App\Domain\FileSystem\Exception\FileSystemException;

/**
 * Creates backups of the source project before conversion.
 * Supports zip, copy, and git strategies.
 *
 * Logic ported from: App\Converter\Directions\ZipWholeFolder
 */
class BackupManager
{
    public function __construct(
        private readonly DirectoryManager $directoryManager,
    ) {}

    /**
     * Create a backup of the source directory.
     *
     * @return string Path to the backup
     * @throws FileSystemException
     */
    public function backup(string $sourcePath, string $backupDir, string $strategy = 'zip'): string
    {
        $this->directoryManager->ensureDirectory($backupDir);

        return match ($strategy) {
            'zip' => $this->createZipBackup($sourcePath, $backupDir),
            'copy' => $this->createCopyBackup($sourcePath, $backupDir),
            default => throw new FileSystemException("Unknown backup strategy: {$strategy}"),
        };
    }

    /**
     * Create a zip archive of the source directory.
     */
    private function createZipBackup(string $sourcePath, string $backupDir): string
    {
        $folderName = basename($sourcePath);
        $timestamp = date('Y_m_d_H_i_s');
        $zipPath = $backupDir . DIRECTORY_SEPARATOR . "{$folderName}_{$timestamp}.zip";

        $zip = new \ZipArchive();
        $result = $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($result !== true) {
            throw new FileSystemException("Failed to create zip archive: {$zipPath}");
        }

        $rootPath = realpath($sourcePath);
        if ($rootPath === false) {
            throw new FileSystemException("Source path does not exist: {$sourcePath}");
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        $fileCount = 0;
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                $zip->addFile($filePath, $relativePath);
                $fileCount++;
            }
        }

        $zip->close();

        // Verify
        if (!file_exists($zipPath)) {
            throw new FileSystemException("Zip archive was not created: {$zipPath}");
        }

        return $zipPath;
    }

    /**
     * Create a full directory copy as backup.
     */
    private function createCopyBackup(string $sourcePath, string $backupDir): string
    {
        $folderName = basename($sourcePath);
        $timestamp = date('Y_m_d_H_i_s');
        $targetDir = $backupDir . DIRECTORY_SEPARATOR . "{$folderName}_{$timestamp}";

        $this->directoryManager->copyDirectory($sourcePath, $targetDir);

        return $targetDir;
    }

    /**
     * Estimate backup size (for dry-run reporting).
     */
    public function estimateSize(string $sourcePath): int
    {
        $size = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
