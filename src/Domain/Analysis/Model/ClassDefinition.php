<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Model;

class ClassDefinition
{
    public function __construct(
        public string $originalName,
        public string $filePath,
        public ClassType $type,
        public string $namespace = '',
        public string $className = '',
        public string $fullyQualifiedName = '',
        public ?string $extends = null,
        /** @var string[] */
        public array $implements = [],
        /** @var string[] */
        public array $uses = [],
    ) {}
}
