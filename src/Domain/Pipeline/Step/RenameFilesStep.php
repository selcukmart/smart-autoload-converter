<?php

declare(strict_types=1);

namespace App\Domain\Pipeline\Step;

use App\Domain\FileSystem\Service\DirectoryManager;
use App\Domain\Pipeline\Contract\PipelineStepInterface;
use App\Domain\Pipeline\Model\PipelineContext;
use App\Domain\Pipeline\Model\PipelineStepStatus;
use App\Domain\Pipeline\Model\StepResult;

/**
 * Step 5: Move class files to their new PSR-4 directory structure.
 * Example: include/MyLib/User/MyLib_user_construct.php -> src/MyLib/User/User.php
 */
class RenameFilesStep implements PipelineStepInterface
{
    public function __construct(
        private readonly DirectoryManager $directoryManager,
    ) {}

    public function getName(): string { return 'rename_files'; }
    public function getPriority(): int { return 500; }
    public function supports(PipelineContext $context): bool { return true; }

    public function execute(PipelineContext $context): StepResult
    {
        $startTime = microtime(true);
        $rules = $context->getConversionRules();
        $filesChanged = 0;
        $moves = [];

        foreach ($rules as $rule) {
            if (empty($rule->oldFilePath) || empty($rule->newFilePath)) {
                continue;
            }

            // Source path in output directory (after PrepareOutputStep copied it)
            $sourceInOutput = $context->toOutputPath($rule->oldFilePath);

            if ($sourceInOutput === $rule->newFilePath) {
                continue;
            }

            if ($context->dryRun) {
                $moves[$sourceInOutput] = $rule->newFilePath;
                $filesChanged++;
                continue;
            }

            $this->directoryManager->moveFile($sourceInOutput, $rule->newFilePath);
            $moves[$sourceInOutput] = $rule->newFilePath;
            $filesChanged++;

            // Update context: content is now at the new path
            $cachedContent = $context->getFileContent($sourceInOutput)
                ?? $context->getFileContent($rule->oldFilePath);
            if ($cachedContent !== null) {
                $context->setFileContent($rule->newFilePath, $cachedContent);
            }
        }

        $context->setStepData('rename_files', ['moves' => $moves]);

        return new StepResult(
            stepName: $this->getName(),
            status: PipelineStepStatus::Completed,
            filesChanged: $filesChanged,
            changes: [
                'files_moved' => $filesChanged,
                'moves' => $moves,
            ],
            durationSeconds: microtime(true) - $startTime,
        );
    }
}
