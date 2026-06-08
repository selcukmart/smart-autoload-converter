<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Conversion\Service;

use SmartAutoloadConverter\Domain\Conversion\Model\ConversionRule;

/**
 * Transforms underscore-separated legacy class names into PSR-4 namespace + class.
 *
 * Example: MyLib_user_construct -> namespace MyLib\User, class User
 * FQCN: MyLib\User\User
 *
 * Transformation rules (suffix mappings, reserved word fixes) are loaded from
 * YAML configuration, not hardcoded.
 */
class ClassNameTransformer
{
    /**
     * @param array<string, string> $suffixTransforms Suffix -> replacement map (from config)
     * @param array<string, string> $reservedWordFixes PHP reserved word -> safe alternative
     * @param string $separator Character used as pseudo-namespace separator in legacy names
     */
    public function __construct(
        private readonly array $suffixTransforms = [],
        private readonly array $reservedWordFixes = [],
        private readonly string $separator = '_',
    ) {}

    /**
     * Transform a legacy class name into a ConversionRule with namespace + class.
     */
    public function transform(string $legacyClassName): ConversionRule
    {
        $parts = explode($this->separator, $legacyClassName);

        if (count($parts) <= 1) {
            // No separator found, class stays as-is with empty namespace
            $className = $this->fixReservedWord($legacyClassName);
            return new ConversionRule(
                oldClassName: $legacyClassName,
                newClassName: $className,
                newNamespace: '',
                newFullyQualifiedName: $className,
                oldFilePath: '',
                newFilePath: '',
            );
        }

        // Last segment is the potential suffix
        $lastPart = end($parts);
        $suffixResult = $this->applySuffixTransform($lastPart);

        if ($suffixResult === '') {
            // Suffix maps to empty string (e.g., 'construct' -> ''): drop it
            array_pop($parts);
        } elseif ($suffixResult !== $lastPart) {
            // Suffix was transformed: replace the last part
            $parts[count($parts) - 1] = $suffixResult;
        }

        // Build namespace from remaining parts (capitalize each)
        $namespaceParts = array_map(fn(string $part) => ucfirst($part), $parts);

        // Fix reserved words in namespace parts
        $namespaceParts = array_map(fn(string $part) => $this->fixReservedWord($part), $namespaceParts);

        // The class name is the last part, namespace is everything before it
        $className = array_pop($namespaceParts);
        $namespace = implode('\\', $namespaceParts);

        // If className is empty after suffix removal, use the last namespace part
        if ($className === '' && count($namespaceParts) > 0) {
            $className = end($namespaceParts);
        }

        $fqcn = $namespace !== '' ? $namespace . '\\' . $className : $className;

        return new ConversionRule(
            oldClassName: $legacyClassName,
            newClassName: $className,
            newNamespace: $namespace,
            newFullyQualifiedName: $fqcn,
            oldFilePath: '',
            newFilePath: '',
        );
    }

    /**
     * Apply suffix transformation from config.
     */
    private function applySuffixTransform(string $suffix): string
    {
        $lowerSuffix = strtolower($suffix);

        foreach ($this->suffixTransforms as $pattern => $replacement) {
            if (strtolower($pattern) === $lowerSuffix) {
                return $replacement;
            }
        }

        return $suffix;
    }

    /**
     * Fix PHP reserved words that cannot be used as namespace segments.
     */
    private function fixReservedWord(string $word): string
    {
        return $this->reservedWordFixes[$word] ?? $word;
    }
}
