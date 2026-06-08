# Smart Autoload Converter

[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4%2B-blue.svg)](https://www.php.net/)
[![Symfony 7/8](https://img.shields.io/badge/Symfony-7%20|%208-black.svg)](https://symfony.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/Tests-82%20passed-brightgreen.svg)](tests/)
[![Docker](https://img.shields.io/badge/Docker-ready-2496ED.svg)](Dockerfile)

Convert legacy PHP projects from `include/require` to **PSR-4 autoloading** — automatically.

Born from a real migration of **6,669 PHP files**, **10,602 include/require statements**, and **4,087 class definitions** across a 1.6 million-line legacy codebase. Now open-sourced as a generic tool any PHP developer can use.

## What It Does

```
BEFORE                              AFTER
├── include/                        ├── src/
│   └── MyLib/                      │   └── MyLib/
│       └── User/                   │       └── User/
│           └── MyLib_user_construct.php    └── User.php ← namespaced
│                                           namespace MyLib\User;
include_once 'include/...';         use MyLib\User\User;
$u = new MyLib_user_construct();    $u = new User();
```

## Features

- **8-step pipeline**: Analyze → Backup → Remove includes → Replace references → Rename files → Add namespaces → Update composer.json → Dump autoload
- **Dry-run mode**: Preview all changes before applying
- **10 regex patterns**: Handles `new`, `::`, `extends`, `implements`, `instanceof`, `catch`, function params, return types, and more
- **Reserved word safety**: Automatically renames `Abstract` → `Abstracts`, `Interface` → `Interfaces`, etc.
- **Duplicate name detection**: Flags classes with the same short name in different directories
- **YAML configuration**: No hardcoded paths or project-specific logic
- **Reports**: Console table, JSON, or HTML output
- **Backup**: Zip or copy strategy before any changes

## Quick Start

```bash
# 1. Install
composer require selcukmart/smart-autoload-converter

# 2. Generate config
php bin/console smart:init

# 3. Analyze first (no changes)
php bin/console smart:analyze -t ./my-legacy-project

# 4. Dry-run (preview changes)
php bin/console smart:convert -t ./my-legacy-project --dry-run

# 5. Convert
php bin/console smart:convert -t ./my-legacy-project -e ./converted
```

## Configuration

```yaml
# smart_autoload.yaml
source:
    path: './legacy-project'
output:
    path: './converted-project'

class_naming:
    separator: '_'
    transforms:
        construct: ''    # MyLib_user_construct → MyLib\User\User
        index: ''        # MyLib_page_index → MyLib\Page\Page
    reserved_word_fixes:
        Abstract: Abstracts
        Interface: Interfaces
        List: Lists

backup:
    enabled: true
    strategy: zip

includes:
    remove_class_includes: true
    preserve:
        - vendor/autoload.php

pipeline:
    stop_on_error: true
```

## Architecture

Clean DDD architecture with clear separation of concerns:

```
src/
├── Domain/
│   ├── Analysis/       ClassAnalyzer, DependencyGraph, FileAnalysis
│   ├── Conversion/     ClassNameTransformer, ClassReferenceReplacer, IncludeRemover
│   ├── FileSystem/     FileScanner, FileWriter, BackupManager
│   ├── Pipeline/       Pipeline engine + 8 configurable steps
│   ├── Regex/          Battle-tested patterns (regex101 verified)
│   └── Report/         ConversionReport + JSON/HTML/Console exporters
├── Application/        CLI commands (smart:convert, smart:analyze, smart:init)
└── Infrastructure/     ComposerJsonEditor, ComposerDumper, ConversionLogger
```

## Pipeline Steps

| # | Step | What it does |
|---|------|-------------|
| 1 | `analyze` | Scans all PHP files, builds dependency graph, generates conversion rules |
| 2 | `backup` | Creates zip/copy backup of the source project |
| 3 | `remove_includes` | Removes include/require for class files (autoloading replaces them) |
| 4 | `replace_references` | Updates all class usages: `new`, `extends`, `instanceof`, `::`, etc. |
| 5 | `rename_files` | Moves files to PSR-4 directory structure |
| 6 | `add_namespaces` | Adds `namespace` declarations and `use` statements |
| 7 | `generate_composer` | Updates `composer.json` with PSR-4 autoload entries |
| 8 | `composer_dump` | Runs `composer dump-autoload --optimize` |

Run specific steps: `php bin/console smart:convert --steps=analyze,backup,remove_includes`

## Commands

| Command | Description |
|---------|------------|
| `smart:convert` | Run the full conversion pipeline |
| `smart:analyze` | Analyze only (no file changes) |
| `smart:init` | Generate example YAML config |

## Testing

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit tests/Domain/
vendor/bin/phpunit tests/Integration/
```

## Docker

```bash
# Build dev image
docker compose build app

# Run tests in Docker
docker compose run --rm test

# Analyze a project
docker compose exec app php bin/console smart:analyze -t /workspace/input

# Convert a project (mount your legacy code to workspace/input)
docker compose --profile convert run --rm convert

# One-liner: build + test
docker build --target dev -t smart-autoload-converter:test . \
  && docker run --rm smart-autoload-converter:test vendor/bin/phpunit --no-coverage
```

## Origin Story

This tool was built in 2022 to modernize a real production PHP application with 1.6 million lines of code, zero namespaces, and thousands of `include_once` statements. The original tool (51 files, 5,226 lines) successfully converted the entire codebase in 2 months.

Read the full story: [Medium: 1.6 Million Lines, Zero Namespaces](https://medium.com/@martselcuk)

## License

MIT License. See [LICENSE](LICENSE) for details.

## Author

**Selcuk Mart** — [GitHub](https://github.com/selcukmart) | [Medium](https://medium.com/@martselcuk)
