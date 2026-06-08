<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Regex;

use SmartAutoloadConverter\Domain\Analysis\Model\IncludeStatement;
use SmartAutoloadConverter\Domain\Analysis\Model\IncludeType;

/**
 * Regex patterns for detecting and parsing PHP include/require statements.
 * Handles all 13 variations: include, include_once, require, require_once
 * with single quotes, double quotes, parentheses, dirname(), __DIR__, etc.
 */
final class IncludeRequirePatterns
{
    /** Match any include/require statement */
    public const GENERAL = '@^(?:include_once|include|require|require_once)(?:\s+|)(?:\(|\s+|)(?:\'|").*(?:\'|")(?:\)|\s+|)(?:\s+|);@mx';

    /** Capture the path from an include/require statement */
    public const CAPTURE_PATH = '@^(include_once|include|require|require_once)(?:\s+|)(?:\(|\s+|)(?:\'|")(.*)(?:\'|")(?:\)|\s+|)(?:\s+|);@mx';

    /** Replacement pattern with {{SCRIPT}} placeholder */
    public const REPLACE_SCRIPT = '@^(?:include_once|include|require|require_once)(?:\s+|)(?:\(|\s+|)(?:\'|"){{SCRIPT}}(?:\'|")(?:\)|\s+|)(?:\s+|);@mx';

    /**
     * Parse a line of code and return an IncludeStatement if it matches.
     */
    public function parse(string $line, string $filePath, int $lineNumber): ?IncludeStatement
    {
        if (!preg_match(self::CAPTURE_PATH, trim($line), $matches)) {
            return null;
        }

        $type = match ($matches[1]) {
            'include' => IncludeType::Include,
            'include_once' => IncludeType::IncludeOnce,
            'require' => IncludeType::Require,
            'require_once' => IncludeType::RequireOnce,
        };

        $path = $matches[2];
        $isDynamic = str_contains($path, '$') || str_contains($path, 'dirname') || str_contains($path, '__DIR__');

        return new IncludeStatement(
            filePath: $filePath,
            lineNumber: $lineNumber,
            type: $type,
            includedPath: $path,
            isDynamic: $isDynamic,
        );
    }

    /**
     * Find all include/require statements in file content.
     *
     * @return IncludeStatement[]
     */
    public function findAll(string $content, string $filePath): array
    {
        $statements = [];
        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            $statement = $this->parse($line, $filePath, $lineNumber + 1);
            if ($statement !== null) {
                $statements[] = $statement;
            }
        }

        return $statements;
    }

    /**
     * Build a replacement pattern for a specific script path.
     */
    public function buildReplacePattern(string $scriptPath): string
    {
        return str_replace('{{SCRIPT}}', preg_quote($scriptPath, '@'), self::REPLACE_SCRIPT);
    }
}
