<?php

declare(strict_types=1);

namespace App\Domain\Conversion\Service;

use App\Domain\Analysis\Model\DependencyGraph;
use App\Domain\Analysis\Model\FileAnalysis;
use App\Domain\Analysis\Model\IncludeStatement;
use App\Domain\Conversion\Model\ConversionResult;
use App\Domain\Regex\IncludeRequirePatterns;

/**
 * Removes include/require statements for class files (autoloading handles them)
 * and optionally updates paths for non-class includes.
 *
 * Logic ported from: App\Converter\Directions\DirectionOperations\DetermineIncludeRequireAreas
 */
class IncludeRemover
{
    public function __construct(
        private readonly IncludeRequirePatterns $patterns,
    ) {}

    /**
     * Process a file: remove class-file includes, keep/update non-class includes.
     *
     * @param string[] $preserveList Include paths to never remove
     */
    public function process(
        string $content,
        FileAnalysis $fileAnalysis,
        DependencyGraph $graph,
        array $preserveList = [],
    ): ConversionResult {
        $modifiedContent = $content;
        $replacements = [];
        $changeCount = 0;
        $requiresReview = false;
        $reviewReason = null;

        foreach ($fileAnalysis->includeStatements as $include) {
            // Skip preserved includes
            if ($this->isPreserved($include, $preserveList)) {
                continue;
            }

            // Skip dynamic includes (flag for manual review)
            if ($include->isDynamic) {
                $requiresReview = true;
                $reviewReason = "Dynamic include at line {$include->lineNumber}: {$include->includedPath}";
                continue;
            }

            // Check if the included file defines a class
            if ($this->isClassInclude($include, $graph)) {
                $pattern = $this->patterns->buildReplacePattern($include->includedPath);
                $modifiedContent = preg_replace($pattern, '', $modifiedContent, -1, $count);
                if ($count > 0) {
                    $replacements[$include->includedPath] = '(removed - autoloaded)';
                    $changeCount += $count;
                }
            }
        }

        // Clean up extra blank lines left by removed includes
        $modifiedContent = preg_replace("/\n{3,}/", "\n\n", $modifiedContent);

        return new ConversionResult(
            filePath: $fileAnalysis->filePath,
            originalContent: $content,
            modifiedContent: $modifiedContent,
            replacements: $replacements,
            changeCount: $changeCount,
            requiresManualReview: $requiresReview,
            reviewReason: $reviewReason,
        );
    }

    /**
     * Check if an included file is a class file by looking it up in the graph.
     */
    private function isClassInclude(IncludeStatement $include, DependencyGraph $graph): bool
    {
        // Try exact path match
        foreach ($graph->getClassFiles() as $analysis) {
            if (str_ends_with($analysis->filePath, ltrim($include->includedPath, './'))
                || str_contains($analysis->relativePath, basename($include->includedPath))
            ) {
                return true;
            }
        }
        return false;
    }

    private function isPreserved(IncludeStatement $include, array $preserveList): bool
    {
        foreach ($preserveList as $preserved) {
            if (str_contains($include->includedPath, $preserved)) {
                return true;
            }
        }
        return false;
    }
}
