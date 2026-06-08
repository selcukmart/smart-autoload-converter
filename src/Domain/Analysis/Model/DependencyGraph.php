<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Analysis\Model;

/**
 * Aggregate: Maps every file's analysis and class relationships.
 * Central data structure built by AnalyzeStep, consumed by all subsequent steps.
 */
class DependencyGraph
{
    /** @var array<string, FileAnalysis> keyed by file path */
    private array $fileAnalyses = [];

    /** @var array<string, ClassDefinition> keyed by original class name */
    private array $classMap = [];

    /** @var array<string, string[]> className => [files that use it] */
    private array $usageMap = [];

    /** @var array<string, string[]> className => [classNames with same short name] */
    private array $duplicateNames = [];

    public function addFileAnalysis(FileAnalysis $analysis): void
    {
        $this->fileAnalyses[$analysis->filePath] = $analysis;

        foreach ($analysis->classDefinitions as $classDef) {
            $this->classMap[$classDef->originalName] = $classDef;
        }

        foreach ($analysis->usedClasses as $usedClass) {
            $this->usageMap[$usedClass][] = $analysis->filePath;
        }
    }

    public function getFileAnalysis(string $filePath): ?FileAnalysis
    {
        return $this->fileAnalyses[$filePath] ?? null;
    }

    /** @return FileAnalysis[] */
    public function getAllFileAnalyses(): array
    {
        return $this->fileAnalyses;
    }

    /** @return FileAnalysis[] Only files containing class/interface/trait definitions */
    public function getClassFiles(): array
    {
        return array_filter($this->fileAnalyses, fn(FileAnalysis $a) => $a->isClassFile());
    }

    /** @return FileAnalysis[] Only files with include/require statements */
    public function getFilesWithIncludes(): array
    {
        return array_filter($this->fileAnalyses, fn(FileAnalysis $a) => $a->hasIncludes());
    }

    public function getClassDefinition(string $className): ?ClassDefinition
    {
        return $this->classMap[$className] ?? null;
    }

    /** @return array<string, ClassDefinition> */
    public function getAllClassDefinitions(): array
    {
        return $this->classMap;
    }

    /** @return string[] File paths that use this class */
    public function getClassUsages(string $className): array
    {
        return array_unique($this->usageMap[$className] ?? []);
    }

    /** @return string[] Class names that are never referenced */
    public function getUnusedClasses(): array
    {
        return array_diff(array_keys($this->classMap), array_keys($this->usageMap));
    }

    public function setDuplicateNames(array $duplicates): void
    {
        $this->duplicateNames = $duplicates;
    }

    /** @return array<string, string[]> */
    public function getDuplicateNames(): array
    {
        return $this->duplicateNames;
    }

    public function hasDuplicateName(string $className): bool
    {
        return isset($this->duplicateNames[$className]);
    }

    // === Statistics ===

    public function getTotalFiles(): int
    {
        return count($this->fileAnalyses);
    }

    public function getTotalClasses(): int
    {
        return count($this->classMap);
    }

    public function getTotalIncludes(): int
    {
        $count = 0;
        foreach ($this->fileAnalyses as $analysis) {
            $count += count($analysis->includeStatements);
        }
        return $count;
    }
}
