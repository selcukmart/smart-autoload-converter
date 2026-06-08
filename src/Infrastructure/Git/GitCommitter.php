<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Infrastructure\Git;

use SmartAutoloadConverter\Domain\FileSystem\Exception\FileSystemException;

/**
 * Auto-commits changes after each pipeline step (when enabled).
 * Provides git-based rollback capability for individual steps.
 */
class GitCommitter
{
    /**
     * Initialize a git repo if one doesn't exist.
     */
    public function init(string $projectDir): void
    {
        if (!is_dir($projectDir . '/.git')) {
            $this->exec($projectDir, 'git init');
            $this->exec($projectDir, 'git add -A');
            $this->exec($projectDir, 'git commit -m "smart-autoload-converter: initial state"');
        }
    }

    /**
     * Commit all changes with a step-specific message.
     */
    public function commitStep(string $projectDir, string $stepName, int $filesChanged): void
    {
        $message = "smart-autoload-converter: {$stepName} completed ({$filesChanged} files changed)";

        $this->exec($projectDir, 'git add -A');

        // Check if there are staged changes
        $result = $this->exec($projectDir, 'git diff --cached --quiet', allowFailure: true);
        if ($result['exitCode'] !== 0) {
            // There are changes to commit
            $this->exec($projectDir, 'git commit -m ' . escapeshellarg($message));
        }
    }

    /**
     * @return array{exitCode: int, output: string}
     */
    private function exec(string $dir, string $command, bool $allowFailure = false): array
    {
        $fullCommand = 'cd ' . escapeshellarg($dir) . ' && ' . $command . ' 2>&1';
        exec($fullCommand, $output, $exitCode);
        $outputStr = implode("\n", $output);

        if ($exitCode !== 0 && !$allowFailure) {
            throw new FileSystemException("Git command failed: {$command}\nOutput: {$outputStr}");
        }

        return ['exitCode' => $exitCode, 'output' => $outputStr];
    }
}
