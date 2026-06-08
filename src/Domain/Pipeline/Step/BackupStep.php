<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Pipeline\Step;

use SmartAutoloadConverter\Domain\FileSystem\Service\BackupManager;
use SmartAutoloadConverter\Domain\Pipeline\Contract\PipelineStepInterface;
use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineContext;
use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineStepStatus;
use SmartAutoloadConverter\Domain\Pipeline\Model\StepResult;

/**
 * Step 2: Create a backup of the source project before any modifications.
 */
class BackupStep implements PipelineStepInterface
{
    public function __construct(
        private readonly BackupManager $backupManager,
    ) {}

    public function getName(): string { return 'backup'; }
    public function getPriority(): int { return 200; }

    public function supports(PipelineContext $context): bool
    {
        return (bool) $context->getConfigValue('backup.enabled', true);
    }

    public function execute(PipelineContext $context): StepResult
    {
        $startTime = microtime(true);
        $strategy = $context->getConfigValue('backup.strategy', 'zip');
        $backupDir = $context->getConfigValue('backup.path', './workspace/backups');

        if ($context->dryRun) {
            $estimatedSize = $this->backupManager->estimateSize($context->sourcePath);
            return new StepResult(
                stepName: $this->getName(),
                status: PipelineStepStatus::Completed,
                changes: [
                    'strategy' => $strategy,
                    'estimated_size_bytes' => $estimatedSize,
                    'dry_run' => true,
                ],
                durationSeconds: microtime(true) - $startTime,
            );
        }

        $backupPath = $this->backupManager->backup($context->sourcePath, $backupDir, $strategy);

        $context->setStepData('backup', ['path' => $backupPath]);

        return new StepResult(
            stepName: $this->getName(),
            status: PipelineStepStatus::Completed,
            filesChanged: 1,
            changes: [
                'strategy' => $strategy,
                'backup_path' => $backupPath,
            ],
            durationSeconds: microtime(true) - $startTime,
        );
    }
}
