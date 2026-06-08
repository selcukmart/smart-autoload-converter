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
 * Step 6: Add namespace declarations and use statements to class files.
 * Also renames the class definition itself (MyLib_user_construct -> User).
 *
 * Logic ported from: ChangeClassnameAndAddNamespaceInContent
 */
class AddNamespacesStep implements PipelineStepInterface
{
    public function __construct(
        private readonly ClassReferenceReplacer $replacer,
        private readonly FileWriter $fileWriter,
    ) {}

    public function getName(): string { return 'add_namespaces'; }
    public function getPriority(): int { return 600; }
    public function supports(PipelineContext $context): bool { return true; }

    public function execute(PipelineContext $context): StepResult
    {
        $startTime = microtime(true);
        $rules = $context->getConversionRules();
        $graph = $context->getDependencyGraph();
        $filesChanged = 0;
        $changes = [];

        foreach ($rules as $rule) {
            if (empty($rule->newNamespace)) {
                continue;
            }

            // File is at newFilePath (after RenameFilesStep moved it within output)
            // In dry-run, file is at the output copy of the old path
            $filePath = $rule->newFilePath ?: $context->toOutputPath($rule->oldFilePath);
            $outputOldPath = $context->toOutputPath($rule->oldFilePath);

            $content = $context->getFileContent($filePath)
                ?? $context->getFileContent($outputOldPath)
                ?? $context->getFileContent($rule->oldFilePath)
                ?? (is_file($filePath) ? $this->fileWriter->read($filePath) : null)
                ?? (is_file($outputOldPath) ? $this->fileWriter->read($outputOldPath) : null)
                ?? $this->fileWriter->read($rule->oldFilePath);

            // 1. Rename the class definition in the file
            $content = $this->renameClassDefinition($content, $rule->oldClassName, $rule->newClassName);

            // 2. Collect use statements for classes this file depends on
            $useStatements = $this->collectUseStatements($rule->oldFilePath, $rules, $graph);

            // 3. Add namespace declaration and use statements
            $content = $this->replacer->addNamespaceAndUse($content, $rule->newNamespace, $useStatements);

            if (!$context->dryRun) {
                $this->fileWriter->write($filePath, $content);
            }
            $context->setFileContent($filePath, $content);
            $filesChanged++;
            $changes[$rule->oldClassName] = [
                'namespace' => $rule->newNamespace,
                'new_class' => $rule->newClassName,
                'use_count' => count($useStatements),
            ];
        }

        return new StepResult(
            stepName: $this->getName(),
            status: PipelineStepStatus::Completed,
            filesChanged: $filesChanged,
            changes: [
                'namespaces_added' => $filesChanged,
                'details' => $changes,
            ],
            durationSeconds: microtime(true) - $startTime,
        );
    }

    /**
     * Rename the class/interface/trait definition line.
     * Example: "class MyLib_user_construct" -> "class User"
     */
    private function renameClassDefinition(string $content, string $oldName, string $newName): string
    {
        $prefixes = ['abstract class', 'final class', 'class', 'interface', 'trait', 'enum'];

        foreach ($prefixes as $prefix) {
            $escapedOld = str_replace('_', '\\_', $oldName);
            $pattern = '/^(' . preg_quote($prefix, '/') . ')\s+' . $escapedOld . '\b/m';
            if (preg_match($pattern, $content)) {
                return preg_replace($pattern, '$1 ' . $newName, $content, 1);
            }
        }

        return $content;
    }

    /**
     * Determine which use statements this class file needs
     * based on which other converted classes it references.
     *
     * @param ConversionRule[] $allRules
     * @return string[] Fully qualified class names for use statements
     */
    private function collectUseStatements(string $filePath, array $allRules, $graph): array
    {
        $fileAnalysis = $graph->getFileAnalysis($filePath);
        if ($fileAnalysis === null) {
            return [];
        }

        $useStatements = [];

        foreach ($fileAnalysis->usedClasses as $usedClass) {
            if (isset($allRules[$usedClass])) {
                $rule = $allRules[$usedClass];
                if (!empty($rule->newNamespace)) {
                    $fqcn = $rule->newNamespace . '\\' . $rule->newClassName;
                    $useStatements[] = $fqcn;
                }
            }
        }

        return array_unique($useStatements);
    }
}
