<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Service;

use App\Domain\Analysis\Model\ClassDefinition;
use App\Domain\Analysis\Model\ClassType;
use App\Domain\Analysis\Model\FileAnalysis;
use App\Domain\Regex\ClassUsagePatterns;
use App\Domain\Regex\IncludeRequirePatterns;

/**
 * Scans PHP file content and extracts class definitions, namespace info,
 * and class usages. Pure analysis — no file modification.
 *
 * Logic ported from: App\Converter\Helpers\FileContentAnalysis
 */
class ClassAnalyzer
{
    /** @var array<string, string> Pattern => ClassType mapping */
    private const DEFINITION_PATTERNS = [
        'abstract' => ClassUsagePatterns::ABSTRACT_CLASS_DEFINITION,
        'final'    => ClassUsagePatterns::FINAL_CLASS_DEFINITION,
        'class'    => ClassUsagePatterns::CLASS_DEFINITION,
        'interface'=> ClassUsagePatterns::INTERFACE_DEFINITION,
        'trait'    => ClassUsagePatterns::TRAIT_DEFINITION,
        'enum'     => ClassUsagePatterns::ENUM_DEFINITION,
    ];

    public function __construct(
        private readonly ClassUsagePatterns $usagePatterns,
        private readonly IncludeRequirePatterns $includePatterns,
    ) {}

    /**
     * Analyze a single PHP file: find class definitions, includes, and class usages.
     */
    public function analyze(string $filePath, string $relativePath, string $content): FileAnalysis
    {
        $classDefinitions = $this->findClassDefinitions($content, $filePath);
        $includeStatements = $this->includePatterns->findAll($content, $filePath);
        $usedClasses = $this->usagePatterns->findClassUsages($content);

        [$hasNamespace, $namespace] = $this->detectNamespace($content);

        return new FileAnalysis(
            filePath: $filePath,
            relativePath: $relativePath,
            classDefinitions: $classDefinitions,
            includeStatements: $includeStatements,
            usedClasses: $usedClasses,
            hasNamespace: $hasNamespace,
            existingNamespace: $namespace,
        );
    }

    /**
     * Find all class/interface/trait/enum definitions in file content.
     *
     * @return ClassDefinition[]
     */
    private function findClassDefinitions(string $content, string $filePath): array
    {
        $definitions = [];

        foreach (self::DEFINITION_PATTERNS as $typeKey => $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $className = $matches[1];
                $classType = $this->resolveClassType($typeKey);
                $extends = $this->findExtends($content);
                $implements = $this->findImplements($content);

                $definitions[] = new ClassDefinition(
                    originalName: $className,
                    filePath: $filePath,
                    type: $classType,
                    className: $className,
                    extends: $extends,
                    implements: $implements,
                );

                // Legacy PHP files typically have one class per file
                break;
            }
        }

        return $definitions;
    }

    /**
     * Detect existing namespace declaration.
     *
     * @return array{0: bool, 1: string} [hasNamespace, namespace]
     */
    private function detectNamespace(string $content): array
    {
        if (preg_match(ClassUsagePatterns::NAMESPACE_DECLARATION, $content, $matches)) {
            return [true, trim($matches[1])];
        }
        return [false, ''];
    }

    private function findExtends(string $content): ?string
    {
        if (preg_match(ClassUsagePatterns::EXTENDS_USAGE, $content, $matches)) {
            return $matches[1];
        }
        if (preg_match(ClassUsagePatterns::EXTENDS_WITH_BRACE, $content, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * @return string[]
     */
    private function findImplements(string $content): array
    {
        if (preg_match(ClassUsagePatterns::IMPLEMENTS_USAGE, $content, $matches)) {
            return array_map('trim', explode(',', $matches[1]));
        }
        return [];
    }

    private function resolveClassType(string $typeKey): ClassType
    {
        return match ($typeKey) {
            'abstract' => ClassType::AbstractClass,
            'final', 'class' => ClassType::Class_,
            'interface' => ClassType::Interface_,
            'trait' => ClassType::Trait_,
            'enum' => ClassType::Enum_,
        };
    }
}
