<?php

declare(strict_types=1);

namespace App\Domain\Pipeline\Step;

use App\Domain\Conversion\Service\ClassReferenceReplacer;
use App\Domain\FileSystem\Service\FileWriter;
use App\Domain\Pipeline\Contract\PipelineStepInterface;
use App\Domain\Pipeline\Model\PipelineContext;
use App\Domain\Pipeline\Model\PipelineStepStatus;
use App\Domain\Pipeline\Model\StepResult;

/**
 * Step 4: Replace all class references (new, extends, instanceof, etc.)
 * with their new fully-qualified names throughout the entire codebase.
 */
class ReplaceReferencesStep implements PipelineStepInterface
{
    public function __construct(
        private readonly ClassReferenceReplacer $replacer,
        private readonly FileWriter $fileWriter,
    ) {}

    public function getName(): string { return 'replace_references'; }
    public function getPriority(): int { return 400; }
    public function supports(PipelineContext $context): bool { return true; }

    public function execute(PipelineContext $context): StepResult
    {
        $startTime = microtime(true);
        $graph = $context->getDependencyGraph();
        $rules = $context->getConversionRules();
        $filesChanged = 0;
        $totalReplacements = 0;
        $changes = [];

        if (empty($rules)) {
            return new StepResult(
                stepName: $this->getName(),
                status: PipelineStepStatus::Completed,
                changes: ['message' => 'No conversion rules, nothing to replace'],
                durationSeconds: microtime(true) - $startTime,
            );
        }

        foreach ($graph->getAllFileAnalyses() as $fileAnalysis) {
            $content = $context->getFileContent($fileAnalysis->filePath)
                ?? $this->fileWriter->read($fileAnalysis->filePath);

            // Filter rules to only those relevant to classes used in this file
            $relevantRules = $this->filterRelevantRules($rules, $fileAnalysis->usedClasses);
            if (empty($relevantRules)) {
                continue;
            }

            $result = $this->replacer->replace($content, $relevantRules);

            if ($result->hasChanges()) {
                if (!$context->dryRun) {
                    $this->fileWriter->write($fileAnalysis->filePath, $result->modifiedContent);
                }
                $context->setFileContent($fileAnalysis->filePath, $result->modifiedContent);
                $filesChanged++;
                $totalReplacements += $result->changeCount;
                $changes[$fileAnalysis->relativePath] = $result->replacements;
            }
        }

        return new StepResult(
            stepName: $this->getName(),
            status: PipelineStepStatus::Completed,
            filesChanged: $filesChanged,
            changes: [
                'total_replacements' => $totalReplacements,
                'files_modified' => $filesChanged,
                'details' => $changes,
            ],
            durationSeconds: microtime(true) - $startTime,
        );
    }

    /**
     * @param ConversionRule[] $allRules
     * @param string[] $usedClasses
     * @return ConversionRule[]
     */
    private function filterRelevantRules(array $allRules, array $usedClasses): array
    {
        $relevant = [];
        foreach ($usedClasses as $className) {
            if (isset($allRules[$className])) {
                $relevant[$className] = $allRules[$className];
            }
        }
        return $relevant;
    }
}
