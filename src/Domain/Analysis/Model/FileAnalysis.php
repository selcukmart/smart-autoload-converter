<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Model;

/**
 * Value Object: Complete analysis result for a single PHP file.
 * Contains all class definitions and include statements found.
 */
class FileAnalysis
{
    /**
     * @param ClassDefinition[] $classDefinitions
     * @param IncludeStatement[] $includeStatements
     * @param string[] $usedClasses Class names referenced (new, extends, etc.)
     */
    public function __construct(
        public string $filePath,
        public string $relativePath,
        public array $classDefinitions = [],
        public array $includeStatements = [],
        public array $usedClasses = [],
        public bool $hasNamespace = false,
        public string $existingNamespace = '',
    ) {}

    public function isClassFile(): bool
    {
        return count($this->classDefinitions) > 0;
    }

    public function hasIncludes(): bool
    {
        return count($this->includeStatements) > 0;
    }

    public function getFirstClassDefinition(): ?ClassDefinition
    {
        return $this->classDefinitions[0] ?? null;
    }
}
