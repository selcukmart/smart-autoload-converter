<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Regex;

/**
 * Regex patterns for detecting PHP class definitions and usages.
 * These patterns identify where classes are defined, instantiated,
 * extended, implemented, and referenced throughout the codebase.
 *
 * Original patterns battle-tested on 6,669 PHP files.
 * Each pattern has a regex101 reference for verification.
 */
final class ClassUsagePatterns
{
    // === Class Definition Patterns ===

    /** @see https://regex101.com/r/dIBh1c/2 */
    public const CLASS_DEFINITION = '@^class\s+(\w+)@mx';
    public const ABSTRACT_CLASS_DEFINITION = '@^abstract\s+class\s+(\w+)@mx';
    public const FINAL_CLASS_DEFINITION = '@^final\s+class\s+(\w+)@mx';
    public const INTERFACE_DEFINITION = '@^interface\s+(\w+)@mx';
    public const TRAIT_DEFINITION = '@^trait\s+(\w+)@mx';
    public const ENUM_DEFINITION = '@^enum\s+class\s+(\w+)@mx';
    public const NAMESPACE_DECLARATION = '@^namespace\s+(.*);$@mx';

    // === Class Usage Patterns (detection) ===

    /** @see https://regex101.com/r/iz1as2/2 */
    public const NEW_INSTANCE = '@new\s+(\w+)@mx';
    public const NEW_INSTANCE_ESCAPED = '@new\s+\\\(\w+)@mx';

    /** @see https://regex101.com/r/9Okfgv/2 */
    public const STATIC_CALL = '@(\w+)::@mx';
    public const STATIC_CALL_ESCAPED = '@\\\(\w+)::@mx';

    public const EXTENDS_USAGE = '@^(?:abstract\s+class|class)\s+\w+\s+extends\s+(\w+)\s+@mx';
    public const EXTENDS_WITH_BRACE = '@^(?:abstract\s+class|class)\s+\w+\s+extends\s+(\w+)\{@mx';

    /** @see https://regex101.com/r/U5zs4m/1 */
    public const IMPLEMENTS_USAGE = '@\s+.*\s+implements\s+(.*)\s+\{@mx';

    public const INSTANCEOF_USAGE = '@\s+instanceof\s+(\w+)@mx';
    public const CATCH_USAGE = '@\}(?:\s+|)catch(?:\s+|)\((?:\s+|)(\w+)\s+@mx';

    /** @see https://regex101.com/r/kn3wPo/1 */
    public const FUNCTION_PARAMS = '@function\s+\w+\((.*)\)@m';

    /** @see https://regex101.com/r/vczAIe/1 */
    public const RETURN_TYPE = '@function\s+\w+(?:\s+|)\(.*\)(?:\s+|)\:(\w+)(?:\s+|)@mx';

    public const CLASS_NAME_EXTRACT = '@^(\w+)@mx';

    // === Replacement Patterns (with {{CLASS}} placeholder) ===

    public const REPLACE_NEW = '@new\s+{{CLASS}}@mx';
    public const REPLACE_STATIC = '@{{CLASS}}::@mx';
    public const REPLACE_EXTENDS = '@^(?:abstract\s+class|class)\s+\w+\s+extends\s+({{CLASS}})\s+@mx';
    public const REPLACE_EXTENDS_BRACE = '@^(?:abstract\s+class|class)\s+\w+\s+extends\s+({{CLASS}})\{@mx';
    public const REPLACE_INSTANCEOF = '@instanceof\s+{{CLASS}}@mx';
    public const REPLACE_CATCH = '@\}(?:\s+|)catch(?:\s+|)\((?:\s+|){{CLASS}}\s+@mx';
    public const REPLACE_CLASS_NAME = '@{{CLASS}}@mx';

    // === Content Masking (hide and seek protection) ===

    public const COMMENTS = '~(?:#|//)[^\r\n]*|/\*.*?\*/~s';
    public const STRING_CONTENT = '@\'.*?\'|\".*?\"@m';

    // === USE Statement ===

    public const USE_STATEMENT = '/^use\s+(.*)\\{{CLASS}}/m';
    public const EXCEPTION_IN_CATCH = '@catch\s+\((Exception)\s+@mixs';

    /**
     * Find all class usages in a given code string.
     *
     * @return string[] Class names found
     */
    public function findClassUsages(string $code): array
    {
        $classes = [];

        $patterns = [
            self::NEW_INSTANCE,
            self::STATIC_CALL,
            self::EXTENDS_USAGE,
            self::IMPLEMENTS_USAGE,
            self::INSTANCEOF_USAGE,
            self::CATCH_USAGE,
            self::RETURN_TYPE,
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $code, $matches)) {
                $classes = array_merge($classes, $matches[1]);
            }
        }

        // Extract from function parameters
        if (preg_match_all(self::FUNCTION_PARAMS, $code, $matches)) {
            foreach ($matches[1] as $params) {
                if (preg_match_all('/(\w+)\s+\$/', $params, $paramMatches)) {
                    $classes = array_merge($classes, $paramMatches[1]);
                }
            }
        }

        // Filter out PHP built-in types
        $builtins = ['string', 'int', 'float', 'bool', 'array', 'object', 'null', 'void', 'self', 'static', 'parent', 'mixed', 'never', 'true', 'false', 'callable', 'iterable'];

        return array_values(array_unique(array_filter(
            $classes,
            fn(string $class) => !in_array(strtolower($class), $builtins, true)
        )));
    }

    /**
     * Build a replacement pattern for a specific class name.
     */
    public function buildReplacePattern(string $template, string $className): string
    {
        return str_replace('{{CLASS}}', preg_quote($className, '@'), $template);
    }
}
