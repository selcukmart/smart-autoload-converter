# Smart Autoload Converter

[![PHP 8.5](https://img.shields.io/badge/PHP-8.5-blue.svg)](https://www.php.net/)
[![Symfony 7.4 LTS](https://img.shields.io/badge/Symfony-7.4%20LTS-black.svg)](https://symfony.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Docker](https://img.shields.io/badge/Docker-ready-2496ED.svg)](Dockerfile)

Convert legacy PHP projects from `include/require` to **PSR-4 autoloading** — automatically.

Born from a real migration of **6,669 PHP files**, **10,602 include/require statements**, and **4,087 class definitions** across a 1.6 million-line legacy codebase. Now open-sourced as a generic tool.

## What It Does

```
BEFORE                                    AFTER
├── include/                              ├── src/
│   └── MyLib/User/                       │   └── MyLib/User/
│       └── MyLib_user_construct.php      │       └── User.php
│           class MyLib_user_construct     │           namespace MyLib\User;
│                                         │           class User
include_once 'include/.../User.php';      use MyLib\User\User;
$u = new MyLib_user_construct();          $u = new User();
```

## Features

- **9-step pipeline**: Analyze, Backup, Prepare Output, Remove includes, Replace references, Rename files, Add namespaces, Update composer.json, Dump autoload
- **Dry-run mode**: Preview all changes before applying
- **Source stays untouched**: Output is a separate converted copy
- **10 regex patterns**: `new`, `::`, `extends`, `implements`, `instanceof`, `catch`, function params, return types
- **Reserved word safety**: `Abstract` → `Abstracts`, `Interface` → `Interfaces`, `List` → `Lists`
- **Duplicate name detection**: Flags classes with the same short name
- **YAML configuration**: Fully configurable, no hardcoded paths
- **Reports**: Console, JSON, and HTML output
- **Standalone CLI**: No Symfony project required — works via `composer require`

## Install

```bash
composer require selcukmart/smart-autoload-converter
```

## Usage

```bash
# Analyze a legacy project
vendor/bin/smart-autoload-converter analyze -t /path/to/legacy-project

# Preview conversion (dry-run)
vendor/bin/smart-autoload-converter convert -t /path/to/legacy-project --dry-run

# Full conversion
vendor/bin/smart-autoload-converter convert -t /path/to/legacy-project --export-path=./output

# Generate config file
vendor/bin/smart-autoload-converter init

# Convert with config
vendor/bin/smart-autoload-converter convert -c smart_autoload.yaml -t ./legacy --export-path=./output

# Save report as HTML
vendor/bin/smart-autoload-converter convert -t ./legacy --report=html --report-output=report.html
```

## Docker (with sample project)

```bash
git clone https://github.com/selcukmart/smart-autoload-converter.git
cd smart-autoload-converter

make build       # Build + start + install
make test        # 82 tests, all green
make analyze     # Analyze sample project
make dry-run     # Preview (9/9 steps OK)
make convert     # Full conversion
make shell       # Enter container
make down        # Stop
```

## Pipeline (9 Steps)

| # | Step | What it does |
|---|------|-------------|
| 1 | `analyze` | Scan PHP files, build dependency graph, generate conversion rules |
| 2 | `backup` | Create zip/copy backup of the source |
| 3 | `prepare_output` | Copy source to output directory (source stays untouched) |
| 4 | `remove_includes` | Remove include/require for class files |
| 5 | `replace_references` | Update `new`, `extends`, `instanceof`, `::`, etc. |
| 6 | `rename_files` | Move files to PSR-4 directory structure |
| 7 | `add_namespaces` | Add `namespace` + `use` statements, rename class definitions |
| 8 | `generate_composer` | Create/update `composer.json` with PSR-4 autoload |
| 9 | `composer_dump` | Run `composer dump-autoload --optimize` |

## Architecture

```
SmartAutoloadConverter\
├── Domain/
│   ├── Analysis/       ClassAnalyzer, DependencyGraph, FileAnalysis
│   ├── Conversion/     ClassNameTransformer, ClassReferenceReplacer, IncludeRemover
│   ├── FileSystem/     FileScanner, FileWriter, BackupManager
│   ├── Pipeline/       Pipeline engine + 9 configurable steps
│   ├── Regex/          Battle-tested patterns (regex101 verified)
│   └── Report/         ConversionReport + JSON/HTML/Console exporters
├── Application/        CLI commands (analyze, convert, init, report)
└── Infrastructure/     ComposerJsonEditor, ComposerDumper, GitCommitter
```

## Configuration

```yaml
# smart_autoload.yaml (generate with: smart-autoload-converter init)
source:
    path: './legacy-project'
output:
    path: './converted-output'
class_naming:
    separator: '_'
    transforms:
        construct: ''
    reserved_word_fixes:
        Abstract: Abstracts
        Interface: Interfaces
backup:
    enabled: true
    strategy: zip
pipeline:
    stop_on_error: true
```

## Testing

```bash
vendor/bin/phpunit                  # All tests
vendor/bin/phpunit tests/Domain/    # Unit tests only
vendor/bin/phpunit tests/Integration/  # Integration tests
```

## Origin Story

Built in 2022 to modernize a 1.6M-line legacy PHP application. The original tool (51 files, 5,226 lines) converted 6,669 PHP files in 2 months. Read more: [Medium](https://medium.com/@martselcuk)

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT. See [LICENSE](LICENSE).

## Author

**Selcuk Mart** - [GitHub](https://github.com/selcukmart) | [Medium](https://medium.com/@martselcuk)
