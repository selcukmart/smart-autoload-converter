<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Infrastructure\Composer;

use SmartAutoloadConverter\Domain\FileSystem\Exception\FileSystemException;

/**
 * Runs composer dump-autoload to regenerate the autoloader.
 */
class ComposerDumper
{
    /**
     * Run composer dump-autoload in the given project directory.
     *
     * @return array{exitCode: int, output: string}
     * @throws FileSystemException
     */
    public function dump(string $projectDir, bool $optimize = true): array
    {
        $command = 'cd ' . escapeshellarg($projectDir) . ' && composer dump-autoload';
        if ($optimize) {
            $command .= ' --optimize';
        }
        $command .= ' 2>&1';

        exec($command, $output, $exitCode);
        $outputStr = implode("\n", $output);

        if ($exitCode !== 0) {
            throw new FileSystemException("composer dump-autoload failed (exit {$exitCode}): {$outputStr}");
        }

        return ['exitCode' => $exitCode, 'output' => $outputStr];
    }
}
