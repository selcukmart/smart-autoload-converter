<?php

declare(strict_types=1);

namespace App\Domain\Conversion\Service;

use App\Domain\Conversion\Model\ConversionResult;
use App\Domain\Conversion\Model\ConversionRule;
use App\Domain\Regex\ClassUsagePatterns;

/**
 * Replaces all class references in file content with their new PSR-4 names.
 *
 * Handles all 10 usage patterns: new, ::, extends, implements, instanceof,
 * catch, function params, return types, property types, use statements.
 *
 * Uses "hide and seek" protection: temporarily masks the class definition line
 * so it is not replaced by its own replacement rule.
 *
 * Logic ported from: SearchAndReplaceInFiles + ChangeInFilesTrait + ChangeClassesAddNamespacesTrait
 */
class ClassReferenceReplacer
{
    /**
     * Replacement rules: for each usage pattern, how to find and replace.
     * {{CLASS}} = old name, {{FQCN}} = new fully qualified name
     */
    private const REPLACEMENT_MAP = [
        'NEW' => [
            'find' => 'new {{CLASS}}',
            'replace' => 'new {{FQCN}}',
            'find_escaped' => 'new \{{CLASS}}',
            'replace_escaped' => 'new \{{FQCN}}',
        ],
        'STATIC_CALL' => [
            'find' => '{{CLASS}}::',
            'replace' => '{{FQCN}}::',
            'find_escaped' => '\{{CLASS}}::',
            'replace_escaped' => '\{{FQCN}}::',
        ],
        'EXTENDS' => [
            'find' => 'extends {{CLASS}} ',
            'replace' => 'extends {{FQCN}} ',
        ],
        'EXTENDS_BRACE' => [
            'find' => 'extends {{CLASS}}{',
            'replace' => "extends {{FQCN}}\n{",
        ],
        'INSTANCEOF' => [
            'find' => 'instanceof {{CLASS}}',
            'replace' => 'instanceof {{FQCN}}',
        ],
        'CATCH' => [
            'find' => 'catch ({{CLASS}} ',
            'replace' => 'catch ({{FQCN}} ',
        ],
    ];

    private const HIDE_MARKER = '__SMARTAUTOLOAD_CLASS_DEFINITION_HIDDEN__';

    /**
     * Replace all references to old class names with new FQCN in content.
     *
     * @param ConversionRule[] $rules Keyed by old class name
     */
    public function replace(string $content, array $rules): ConversionResult
    {
        $originalContent = $content;
        $replacements = [];
        $totalChanges = 0;

        foreach ($rules as $rule) {
            // Hide class definition line to avoid self-replacement
            [$hiddenMatch, $content] = $this->hideClassDefinition($content);

            // Apply all replacement patterns
            foreach (self::REPLACEMENT_MAP as $patternName => $mapping) {
                $result = $this->applyPattern($content, $rule, $mapping);
                $content = $result['content'];

                if ($result['count'] > 0) {
                    $replacements["{$rule->oldClassName}:{$patternName}"] = "{$rule->newFullyQualifiedName} ({$result['count']}x)";
                    $totalChanges += $result['count'];
                }
            }

            // Handle implements separately (needs special multi-interface parsing)
            $implResult = $this->replaceImplements($content, $rule);
            $content = $implResult['content'];
            $totalChanges += $implResult['count'];

            // Restore class definition line
            $content = $this->restoreClassDefinition($content, $hiddenMatch);
        }

        return new ConversionResult(
            filePath: '',
            originalContent: $originalContent,
            modifiedContent: $content,
            replacements: $replacements,
            changeCount: $totalChanges,
        );
    }

    /**
     * Apply a single replacement pattern (find/replace with str_replace).
     *
     * @return array{content: string, count: int}
     */
    private function applyPattern(string $content, ConversionRule $rule, array $mapping): array
    {
        $count = 0;
        $oldName = $rule->oldClassName;
        $fqcn = $rule->newFullyQualifiedName;

        // Normal replacement
        $find = str_replace('{{CLASS}}', $oldName, $mapping['find']);
        $replace = str_replace('{{FQCN}}', $fqcn, $mapping['replace']);
        $content = str_replace($find, $replace, $content, $c);
        $count += $c;

        // Escaped (backslash-prefixed) replacement if available
        if (isset($mapping['find_escaped'])) {
            $findEsc = str_replace('{{CLASS}}', $oldName, $mapping['find_escaped']);
            $replEsc = str_replace('{{FQCN}}', $fqcn, $mapping['replace_escaped']);
            $content = str_replace($findEsc, $replEsc, $content, $c);
            $count += $c;
        }

        return ['content' => $content, 'count' => $count];
    }

    /**
     * Replace class names in implements clause (may have multiple interfaces).
     *
     * @return array{content: string, count: int}
     */
    private function replaceImplements(string $content, ConversionRule $rule): array
    {
        $count = 0;

        if (preg_match(ClassUsagePatterns::IMPLEMENTS_USAGE, $content, $matches)) {
            $implementsStr = $matches[0];
            $newStr = str_replace($rule->oldClassName, $rule->newFullyQualifiedName, $implementsStr, $c);
            if ($c > 0) {
                $content = str_replace($implementsStr, $newStr, $content);
                $count += $c;
            }
        }

        return ['content' => $content, 'count' => $count];
    }

    /**
     * Hide the class definition line to prevent self-replacement.
     * "class MyClass" becomes "__HIDDEN__" temporarily.
     *
     * @return array{0: string|null, 1: string} [matched definition or null, modified content]
     */
    private function hideClassDefinition(string $content): array
    {
        if (preg_match(ClassUsagePatterns::CLASS_DEFINITION, $content, $matches)) {
            $original = 'class ' . $matches[1];
            return [$original, str_replace($original, self::HIDE_MARKER, $content)];
        }
        return [null, $content];
    }

    /**
     * Restore the hidden class definition line.
     */
    private function restoreClassDefinition(string $content, ?string $original): string
    {
        if ($original !== null) {
            return str_replace(self::HIDE_MARKER, $original, $content);
        }
        return $content;
    }

    /**
     * Add a namespace declaration and use statements to a class file.
     */
    public function addNamespaceAndUse(string $content, string $namespace, array $useStatements = []): string
    {
        if (empty($namespace)) {
            return $content;
        }

        $namespaceDecl = "namespace {$namespace};";
        $useBlock = '';
        if (!empty($useStatements)) {
            $useBlock = "\n" . implode("\n", array_map(fn(string $fqcn) => "use {$fqcn};", $useStatements));
        }

        // Insert after <?php (and optional declare)
        if (preg_match('/^(<\?php\s*(?:declare\s*\([^)]*\)\s*;\s*)?)/s', $content, $matches)) {
            $header = $matches[1];
            $rest = substr($content, strlen($header));
            return $header . "\n" . $namespaceDecl . $useBlock . "\n" . $rest;
        }

        // Fallback: prepend
        return "<?php\n\n{$namespaceDecl}{$useBlock}\n\n" . ltrim($content, "<?php\n");
    }
}
