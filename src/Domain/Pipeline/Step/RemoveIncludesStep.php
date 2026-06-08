<?php

declare(strict_types=1);

namespace App\Domain\Pipeline\Step;

use App\Domain\Conversion\Service\IncludeRemover;
use App\Domain\FileSystem\Service\FileWriter;
use App\Domain\Pipeline\Contract\PipelineStepInterface;
use App\Domain\Pipeline\Model\PipelineContext;
use App\Domain\Pipeline\Model\PipelineStepStatus;
use App\Domain\Pipeline\Model\StepResult;

/**
 * Step 3: Remove include/require statements for class files.
 * Non-class includes are preserved or updated. Dynamic includes flagged for review.
 */
class RemoveIncludesStep implements PipelineStepInterface
{
    public function __construct(
        private readonly IncludeRemover $includeRemover,
        private readonly FileWriter $fileWriter,
    ) {}

    public function getName(): string { return 'remove_includes'; }
    public function getPriority(): int { return 300; }
    public function supports(PipelineContext $context): bool { return true; }

    public function execute(PipelineContext $context): StepResult
    {
        $startTime = microtime(true);
        $graph = $context->getDependencyGraph();
        $preserveList = $context->getConfigValue('includes.preserve', []);
        $filesChanged = 0;
        $totalRemoved = 0;
        $changes = [];

        foreach ($graph->getFilesWithIncludes() as $fileAnalysis) {
            $content = $context->getFileContent($fileAnalysis->filePath)
                ?? $this->fileWriter->read($fileAnalysis->filePath);

            $result = $this->includeRemover->process($content, $fileAnalysis, $graph, $preserveList);

            if ($result->hasChanges()) {
                if (!$context->dryRun) {
                    $this->fileWriter->write($fileAnalysis->filePath, $result->modifiedContent);
                }
                $context->setFileContent($fileAnalysis->filePath, $result->modifiedContent);
                $filesChanged++;
                $totalRemoved += $result->changeCount;
                $changes[$fileAnalysis->relativePath] = $result->replacements;
            }

            if ($result->requiresManualReview) {
                $context->addManualReview($fileAnalysis->filePath, $result->reviewReason ?? 'Dynamic include');
            }
        }

        return new StepResult(
            stepName: $this->getName(),
            status: PipelineStepStatus::Completed,
            filesChanged: $filesChanged,
            changes: [
                'includes_removed' => $totalRemoved,
                'files_modified' => $filesChanged,
                'manual_review' => count($context->getManualReviewFiles()),
                'details' => $changes,
            ],
            durationSeconds: microtime(true) - $startTime,
        );
    }
}
