<?php

declare(strict_types=1);

namespace App\Domain\Conversion\Service;

/**
 * Fixes PHP reserved words that cannot be used as namespace segments or class names.
 *
 * Examples: Abstract -> Abstracts, Interface -> Interfaces, List -> Lists
 *
 * Logic ported from: App\Helper\namespaceFix()
 */
class ReservedWordFixer
{
    /** @var array<string, string> */
    private readonly array $reservedWordMap;

    /**
     * @param array<string, string> $customMap Additional reserved word mappings from config
     */
    public function __construct(array $customMap = [])
    {
        $this->reservedWordMap = array_merge(self::DEFAULT_MAP, $customMap);
    }

    private const DEFAULT_MAP = [
        'Abstract' => 'Abstracts',
        'Interface' => 'Interfaces',
        'Trait' => 'Traits',
        'Class' => 'Classes',
        'List' => 'Lists',
        'String' => 'StringOperations',
        'Object' => 'Objects',
        'Php' => 'PhpCodes',
        'STATIC' => 'StaticItems',
        'Function' => 'Functions',
        'Namespace' => 'Namespaces',
        'Match' => 'Matches',
        'Enum' => 'Enums',
        'Readonly' => 'ReadonlyItems',
    ];

    /**
     * Fix a single word if it is a PHP reserved word.
     */
    public function fix(string $word): string
    {
        return $this->reservedWordMap[$word] ?? $word;
    }

    /**
     * Fix all segments in a namespace array.
     *
     * @param string[] $segments
     * @return string[]
     */
    public function fixAll(array $segments): array
    {
        return array_map(fn(string $s) => $this->fix($s), $segments);
    }

    /**
     * Fix reserved words in a fully qualified namespace string.
     */
    public function fixNamespaceString(string $namespace): string
    {
        $parts = explode('\\', $namespace);
        return implode('\\', $this->fixAll($parts));
    }

    /**
     * Check if a word is a PHP reserved word that needs fixing.
     */
    public function isReserved(string $word): bool
    {
        return isset($this->reservedWordMap[$word]);
    }

    /** @return array<string, string> */
    public function getMap(): array
    {
        return $this->reservedWordMap;
    }
}
