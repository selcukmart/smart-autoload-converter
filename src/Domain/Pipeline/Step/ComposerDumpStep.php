<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Pipeline\Step;

use SmartAutoloadConverter\Infrastructure\Composer\ComposerDumper;
use SmartAutoloadConverter\Domain\Pipeline\Contract\PipelineStepInterface;
use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineContext;
use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineStepStatus;
use SmartAutoloadConverter\Domain\Pipeline\Model\StepResult;

/**
 * Step 8: Run composer dump-autoload to regenerate the autoloader.
 * Skipped entirely in dry-run mode.
 */
class ComposerDumpStep implements PipelineStepInterface
{
    public function __construct(
        private readonly ComposerDumper $composerDumper,
    ) {}

    public function getName(): string { return 'composer_dump'; }
    public function getPriority(): int { return 800; }
    public function supports(PipelineContext $context): bool { return !$context->dryRun; }

    public function execute(PipelineContext $context): StepResult
    {
        $startTime = microtime(true);

        $result = $this->composerDumper->dump($context->outputPath);

        return new StepResult(
            stepName: $this->getName(),
            status: PipelineStepStatus::Completed,
            filesChanged: 1,
            changes: [
                'exit_code' => $result['exitCode'],
                'output' => $result['output'],
            ],
            durationSeconds: microtime(true) - $startTime,
        );
    }
}
