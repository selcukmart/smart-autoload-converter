<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Conversion\Service;

use SmartAutoloadConverter\Domain\Analysis\Model\DependencyGraph;
use SmartAutoloadConverter\Domain\Analysis\Model\IncludeStatement;

/**
 * Resolves ambiguous class names when multiple classes share the same short name.
 *
 * Strategy: When two files define "viewController", the resolver checks which
 * include/require statement in the consuming file points to which definition,
 * then assigns distinct FQCN (e.g., Admin\ViewController vs UI\ViewController).
 *
 * Logic ported from: App\Converter\Helpers\ClassOperations\InvestigateMoreSameClassname
 */
class SameNameResolver
{
    /**
     * Resolve which definition a file is referencing when names collide.
     *
     * @param string $className The ambiguous short class name
     * @param string $consumingFilePath The file that uses this class
     * @param DependencyGraph $graph The complete dependency analysis
     * @return string|null The original class name from the correct definition, or null if unresolvable
     */
    public function resolve(string $className, string $consumingFilePath, DependencyGraph $graph): ?string
    {
        $fileAnalysis = $graph->getFileAnalysis($consumingFilePath);
        if ($fileAnalysis === null) {
            return null;
        }

        // Check include statements for a path containing the class filename
        $classFilename = $className . '.php';

        foreach ($fileAnalysis->includeStatements as $include) {
            if ($this->pathContainsFile($include->includedPath, $classFilename)) {
                $resolvedPath = $this->resolveIncludePath($consumingFilePath, $include->includedPath);
                $targetAnalysis = $graph->getFileAnalysis($resolvedPath);
                if ($targetAnalysis !== null && $targetAnalysis->isClassFile()) {
                    $classDef = $targetAnalysis->getFirstClassDefinition();
                    if ($classDef !== null) {
                        return $classDef->originalName;
                    }
                }
            }
        }

        // Fallback: check same directory and parent directory
        $dir = dirname($consumingFilePath);
        $candidates = $this->findCandidatesNearby($className, $dir, $graph);

        return count($candidates) === 1 ? $candidates[0] : null;
    }

    private function pathContainsFile(string $includePath, string $filename): bool
    {
        return str_ends_with($includePath, $filename)
            || str_ends_with($includePath, '/' . $filename);
    }

    /**
     * Resolve relative include path (with ../) to an absolute path.
     */
    private function resolveIncludePath(string $fromFile, string $includePath): string
    {
        $includePath = trim($includePath, " \t'\"");
        $baseDir = dirname($fromFile);

        $parts = explode('/', $includePath);
        $upCount = 0;

        foreach ($parts as $key => $part) {
            if ($part === '..') {
                $upCount++;
                unset($parts[$key]);
            } elseif ($part === '.') {
                unset($parts[$key]);
            }
        }

        $baseParts = explode('/', $baseDir);
        for ($i = 0; $i < $upCount; $i++) {
            array_pop($baseParts);
        }

        return implode('/', $baseParts) . '/' . implode('/', $parts);
    }

    /**
     * Find class definitions near a directory (same dir or parent).
     *
     * @return string[] Original class names of candidates
     */
    private function findCandidatesNearby(string $className, string $dir, DependencyGraph $graph): array
    {
        $candidates = [];
        $filename = $className . '.php';

        foreach ($graph->getClassFiles() as $analysis) {
            $classDef = $analysis->getFirstClassDefinition();
            if ($classDef === null) {
                continue;
            }

            $fileDir = dirname($analysis->filePath);
            $baseName = basename($analysis->filePath);

            if ($baseName === $filename && ($fileDir === $dir || $fileDir === dirname($dir))) {
                $candidates[] = $classDef->originalName;
            }
        }

        return $candidates;
    }
}
