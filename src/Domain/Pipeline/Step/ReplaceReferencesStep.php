<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Pipeline\Step;

use SmartAutoloadConverter\Domain\Conversion\Service\ClassReferenceReplacer;
use SmartAutoloadConverter\Domain\FileSystem\Service\FileWriter;
use SmartAutoloadConverter\Domain\Pipeline\Contract\PipelineStepInterface;
use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineContext;
use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineStepStatus;
use SmartAutoloadConverter\Domain\Pipeline\Model\StepResult;

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
            // Work on the output copy
            // In dry-run, output may not exist — fall back to source
            $workPath = $context->toOutputPath($fileAnalysis->filePath);

            $content = $context->getFileContent($workPath)
                ?? $context->getFileContent($fileAnalysis->filePath)
                ?? (is_file($workPath) ? $this->fileWriter->read($workPath) : null)
                ?? $this->fileWriter->read($fileAnalysis->filePath);

            // Filter rules to only those relevant to classes used in this file
            $relevantRules = $this->filterRelevantRules($rules, $fileAnalysis->usedClasses);
            if (empty($relevantRules)) {
                continue;
            }

            $result = $this->replacer->replace($content, $relevantRules);

            if ($result->hasChanges()) {
                if (!$context->dryRun) {
                    $this->fileWriter->write($workPath, $result->modifiedContent);
                }
                $context->setFileContent($workPath, $result->modifiedContent);
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
