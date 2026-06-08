<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Pipeline\Step;

use SmartAutoloadConverter\Domain\FileSystem\Service\DirectoryManager;
use SmartAutoloadConverter\Domain\Pipeline\Contract\PipelineStepInterface;
use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineContext;
use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineStepStatus;
use SmartAutoloadConverter\Domain\Pipeline\Model\StepResult;

/**
 * Step 2.5: Copy the entire source directory to the output directory.
 * All subsequent steps work on the output copy — the source stays untouched.
 */
class PrepareOutputStep implements PipelineStepInterface
{
    public function __construct(
        private readonly DirectoryManager $directoryManager,
    ) {}

    public function getName(): string { return 'prepare_output'; }
    public function getPriority(): int { return 250; }
    public function supports(PipelineContext $context): bool { return true; }

    public function execute(PipelineContext $context): StepResult
    {
        $startTime = microtime(true);

        if ($context->dryRun) {
            return new StepResult(
                stepName: $this->getName(),
                status: PipelineStepStatus::Completed,
                changes: ['dry_run' => true, 'source' => $context->sourcePath, 'output' => $context->outputPath],
                durationSeconds: microtime(true) - $startTime,
            );
        }

        $this->directoryManager->ensureDirectory($context->outputPath);
        $fileCount = $this->directoryManager->copyDirectory($context->sourcePath, $context->outputPath);

        return new StepResult(
            stepName: $this->getName(),
            status: PipelineStepStatus::Completed,
            filesChanged: $fileCount,
            changes: [
                'files_copied' => $fileCount,
                'source' => $context->sourcePath,
                'output' => $context->outputPath,
            ],
            durationSeconds: microtime(true) - $startTime,
        );
    }
}
