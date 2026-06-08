<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Pipeline\Step;

use SmartAutoloadConverter\Domain\Analysis\Service\ClassAnalyzer;
use SmartAutoloadConverter\Domain\Analysis\Service\DependencyGraphBuilder;
use SmartAutoloadConverter\Domain\Conversion\Model\ConversionRule;
use SmartAutoloadConverter\Domain\Conversion\Service\ClassNameTransformer;
use SmartAutoloadConverter\Domain\FileSystem\Service\FileScanner;
use SmartAutoloadConverter\Domain\FileSystem\Service\FileWriter;
use SmartAutoloadConverter\Domain\Pipeline\Contract\PipelineStepInterface;
use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineContext;
use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineStepStatus;
use SmartAutoloadConverter\Domain\Pipeline\Model\StepResult;

/**
 * Step 1: Scan all PHP files, analyze class definitions, includes, and usages.
 * Builds the DependencyGraph and generates ConversionRules.
 */
class AnalyzeStep implements PipelineStepInterface
{
    public function __construct(
        private readonly FileScanner $fileScanner,
        private readonly DependencyGraphBuilder $graphBuilder,
        private readonly ClassNameTransformer $classNameTransformer,
    ) {}

    public function getName(): string { return 'analyze'; }
    public function getPriority(): int { return 100; }
    public function supports(PipelineContext $context): bool { return true; }

    public function execute(PipelineContext $context): StepResult
    {
        $startTime = microtime(true);

        // 1. Scan files
        $ignorePatterns = $context->getConfigValue('ignore.patterns', []);
        $ignoreFiles = $context->getConfigValue('ignore.files', []);
        $files = $this->fileScanner->scan($context->sourcePath, $ignorePatterns, $ignoreFiles);

        // 2. Build dependency graph
        $graph = $this->graphBuilder->build($files);

        // 3. Generate conversion rules for each class definition
        $rules = [];
        foreach ($graph->getAllClassDefinitions() as $classDef) {
            if ($classDef->namespace !== '') {
                // Already namespaced, skip conversion
                continue;
            }

            $rule = $this->classNameTransformer->transform($classDef->originalName);
            $rules[$classDef->originalName] = new ConversionRule(
                oldClassName: $classDef->originalName,
                newClassName: $rule->newClassName,
                newNamespace: $rule->newNamespace,
                newFullyQualifiedName: $rule->newFullyQualifiedName,
                oldFilePath: $classDef->filePath,
                newFilePath: $this->buildPsr4Path($context->outputPath, $rule),
            );
        }

        // 4. Store results in context
        $context->setDependencyGraph($graph);
        $context->setConversionRules($rules);

        return new StepResult(
            stepName: $this->getName(),
            status: PipelineStepStatus::Completed,
            filesChanged: 0,
            changes: [
                'total_files' => $graph->getTotalFiles(),
                'total_classes' => $graph->getTotalClasses(),
                'total_includes' => $graph->getTotalIncludes(),
                'conversion_rules' => count($rules),
                'duplicate_names' => count($graph->getDuplicateNames()),
                'unused_classes' => count($graph->getUnusedClasses()),
            ],
            durationSeconds: microtime(true) - $startTime,
        );
    }

    private function buildPsr4Path(string $outputPath, ConversionRule $rule): string
    {
        $namespacePath = str_replace('\\', DIRECTORY_SEPARATOR, $rule->newNamespace);
        return rtrim($outputPath, '/') . DIRECTORY_SEPARATOR . $namespacePath . DIRECTORY_SEPARATOR . $rule->newClassName . '.php';
    }
}
