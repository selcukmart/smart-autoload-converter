# 03 - Modernization Plan

## Objective

Upgrade from PHP 8.1 / Symfony 6.1 to PHP 8.5 / Symfony 7.4 LTS, applying modern PHP features throughout.

---

## PHP 8.5 Features to Apply

### Readonly Classes and Properties

```php
// Before
class ClassDefinition {
    private string $name;
    public function getName(): string { return $this->name; }
}

// After
readonly class ClassDefinition {
    public function __construct(
        public string $name,
        public string $filePath,
        public string $namespace,
        public ClassType $type,
    ) {}
}
```

### Enums (replace string constants)

```php
// Before: string constants scattered in code
const TYPE_CLASS = 'class';
const TYPE_INTERFACE = 'interface';
const TYPE_TRAIT = 'trait';

// After
enum ClassType: string {
    case Class_ = 'class';
    case Interface_ = 'interface';
    case Trait_ = 'trait';
    case Enum_ = 'enum';
    case AbstractClass = 'abstract_class';
}

enum PipelineStepStatus: string {
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Skipped = 'skipped';
}

enum IncludeType: string {
    case Include = 'include';
    case IncludeOnce = 'include_once';
    case Require = 'require';
    case RequireOnce = 'require_once';
}
```

### Strict Types Everywhere

```php
declare(strict_types=1);
```

Every single PHP file must have this declaration.

### Match Expressions (replace switch)

```php
// Before
switch ($type) {
    case 'class': return 'Class'; break;
    case 'interface': return 'Interface'; break;
    default: throw new \InvalidArgumentException();
}

// After
return match($type) {
    ClassType::Class_ => 'Class',
    ClassType::Interface_ => 'Interface',
    default => throw new \InvalidArgumentException("Unknown type: {$type->value}"),
};
```

### Named Arguments

```php
// Before
$transformer->transform($name, true, false, 'PRF');

// After
$transformer->transform(
    className: $name,
    preservePrefix: true,
    stripSuffix: false,
    namespaceRoot: 'PRF',
);
```

### First-class Callable Syntax

```php
// Before
array_map(function($file) { return $file->getPath(); }, $files);

// After
array_map($file->getPath(...), $files);
```

### Fibers (if applicable for large codebases)

Consider Fibers for non-blocking file I/O when processing thousands of files.

---

## Symfony 7.4 Migration

### Dependency Updates

| Package | Current | Target |
|---------|---------|--------|
| `symfony/console` | 6.1 | 7.4 |
| `symfony/framework-bundle` | 6.1 | 7.4 |
| `symfony/yaml` | 6.1 | 7.4 |
| `symfony/finder` | (new) | 7.4 |
| `symfony/filesystem` | (new) | 7.4 |
| `symfony/process` | (new) | 7.4 |
| `symfony/validator` | (new) | 7.4 |
| `phpunit/phpunit` | (new) | 11.x |
| `phpstan/phpstan` | (new) | 2.x |

### Remove Unnecessary Dependencies

Remove Doctrine ORM, Twig, AssetMapper, and all web-related packages. This is a CLI tool, not a web application.

### Symfony Attributes (replace annotations)

```php
// Command
#[AsCommand(
    name: 'smart:convert',
    description: 'Convert a legacy PHP project to PSR-4 autoloading',
)]
class ConvertCommand extends Command {}

// Service autowiring
#[Autoconfigure(lazy: true)]
class ClassAnalyzer {}

// Tagged services
#[AutoconfigureTag('smart.pipeline_step')]
interface PipelineStepInterface {}
```

---

## Code Quality Tools

### PHPStan Level 9

```neon
# phpstan.neon
parameters:
    level: 9
    paths:
        - src
        - tests
```

### PHP CS Fixer

```php
// .php-cs-fixer.dist.php
return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PHP84Migration' => true,
        'strict_types' => true,
        'declare_strict_types' => true,
    ]);
```

### Rector (automated refactoring)

Use Rector to automatically apply PHP 8.5 patterns during the migration.

---

## Patterns to Eliminate

| Anti-Pattern | Replacement |
|-------------|-------------|
| Traits (10 total) | Service classes with DI |
| Static methods | Instance methods, injectable |
| Global functions (`Helpers.php`) | Service classes |
| `new ClassName()` inside classes | Constructor injection |
| Array configs | Typed configuration objects |
| `echo` for output | Symfony Console OutputInterface |
| Custom logger (5 files) | PSR-3 Logger (Monolog) |
| `file_get_contents` / `file_put_contents` | Symfony Filesystem component |
