<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Analysis\Service;

use SmartAutoloadConverter\Domain\Analysis\Model\DependencyGraph;
use SmartAutoloadConverter\Domain\FileSystem\Model\ScannedFile;
use SmartAutoloadConverter\Domain\FileSystem\Service\FileWriter;

/**
 * Orchestrates file scanning + analysis to build a complete DependencyGraph.
 *
 * Logic ported from: AnalyzeOfFiles direction + DetermineClassnamesTrait
 */
class DependencyGraphBuilder
{
    public function __construct(
        private readonly ClassAnalyzer $classAnalyzer,
        private readonly FileWriter $fileReader,
    ) {}

    /**
     * Build a DependencyGraph from a list of scanned files.
     *
     * @param ScannedFile[] $files
     */
    public function build(array $files): DependencyGraph
    {
        $graph = new DependencyGraph();

        foreach ($files as $file) {
            $content = $this->fileReader->read($file->path);
            $analysis = $this->classAnalyzer->analyze($file->path, $file->relativePath, $content);
            $graph->addFileAnalysis($analysis);
        }

        // Detect duplicate class names (same short name in different files)
        $duplicates = $this->detectDuplicateNames($graph);
        $graph->setDuplicateNames($duplicates);

        return $graph;
    }

    /**
     * Find classes that share the same short name (e.g., viewController in two dirs).
     *
     * @return array<string, string[]> shortName => [originalName1, originalName2, ...]
     */
    private function detectDuplicateNames(DependencyGraph $graph): array
    {
        $shortNameMap = [];

        foreach ($graph->getAllClassDefinitions() as $className => $definition) {
            $shortName = $this->getShortName($className);
            $shortNameMap[$shortName][] = $className;
        }

        return array_filter($shortNameMap, fn(array $names) => count($names) > 1);
    }

    private function getShortName(string $className): string
    {
        $parts = explode('_', $className);
        return end($parts);
    }
}
