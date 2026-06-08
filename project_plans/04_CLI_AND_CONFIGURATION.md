# 04 - CLI and Configuration Design

## Objective

Replace the HTTP-triggered controller with Symfony Console commands and a YAML-based configuration system. The tool should be fully operable from the command line.

---

## Symfony Commands

### smart:convert (Main Command)

```bash
# Full conversion
php bin/console smart:convert --config=smart-autoload-converter.yaml

# Dry run (preview changes without applying)
php bin/console smart:convert --config=config.yaml --dry-run

# Specific steps only
php bin/console smart:convert --config=config.yaml --steps=analyze,backup,namespace

# Verbose output
php bin/console smart:convert --config=config.yaml -vvv

# Output report
php bin/console smart:convert --config=config.yaml --report=json --report-output=report.json
```

### smart:analyze (Analysis Only)

```bash
# Analyze and show summary
php bin/console smart:analyze /path/to/legacy-project

# Detailed analysis with file list
php bin/console smart:analyze /path/to/legacy-project --detailed

# Export analysis as JSON
php bin/console smart:analyze /path/to/legacy-project --format=json --output=analysis.json
```

### smart:init (Generate Configuration)

```bash
# Scan a project and generate a starter config file
php bin/console smart:init /path/to/legacy-project --output=smart-autoload-converter.yaml
```

This command scans the target project, detects class naming patterns, include/require patterns, and directory structure, then generates a pre-filled YAML configuration.

### smart:report (Generate Report)

```bash
# Generate HTML report from a previous conversion
php bin/console smart:report --format=html --output=report.html

# Generate JSON report
php bin/console smart:report --format=json --output=report.json
```

---

## YAML Configuration Schema

```yaml
# smart-autoload-converter.yaml

# Source project to convert
source:
  path: /path/to/legacy-project
  
# Where to write the converted output (optional, defaults to in-place)
output:
  path: /path/to/output              # null = modify source in place
  
# Backup before conversion
backup:
  enabled: true
  strategy: zip                       # zip | git | copy
  path: ./backups/

# Class name transformation rules
class_naming:
  separator: "_"                      # character used as pseudo-namespace separator
  transforms:                         # suffix transformations
    construct: ""                     # MyLib_user_construct -> MyLib\User
    list: "List"                      # MyLib_user_list -> MyLib\User\List
    view: "View"
    controller: "Controller"
    factory: "Factory"
    abstract: "Abstract"
    interface: "Interface"
  reserved_word_fixes:                # PHP reserved words in namespaces
    Abstract: "Abstracts"
    Interface: "Interfaces"
    Trait: "Traits"
    Class: "Classes"
    List: "Lists"

# Namespace mapping: directory path -> namespace root
namespaces:
  "/include/": ""                     # /include/MyLib/... -> MyLib\...
  "/Zend": "Zend1"                    # /Zend/... -> Zend1\...
  "/lib": "Lib"

# Directories to scan for class references (replacement targets)
scan_directories:
  - /path/to/legacy-project/include
  - /path/to/legacy-project/scripts
  - /path/to/legacy-project/public

# Files and directories to ignore
ignore:
  patterns:
    - "vendor/"
    - "node_modules/"
    - ".git/"
    - "*.min.js"
  files:
    - "config.php"                    # specific files to skip

# Include/require handling
includes:
  remove_class_includes: true         # remove includes that load class files
  update_non_class_paths: true        # update paths for non-class includes
  preserve:                           # never remove these includes
    - "vendor/autoload.php"
    - "bootstrap.php"

# Pipeline configuration
pipeline:
  steps:
    - analyze
    - backup
    - remove_includes
    - replace_references
    - rename_files
    - add_namespaces
    - generate_composer
    - composer_dump
  git_commit_per_step: false          # auto-commit after each step

# Composer.json updates
composer:
  autoload:
    psr4:
      "MyLib\\": "src/MyLib/"
      "Zend1\\": "src/Zend1/"
  update_existing: true               # modify existing composer.json

# Report output
report:
  enabled: true
  format: json                        # json | html | console
  output: conversion-report.json

# Dry run (preview only, no changes)
dry_run: false

# Logging
logging:
  level: info                         # debug | info | warning | error
  file: conversion.log
```

---

## Configuration Validation

The `ConfigurationLoader` must validate:

- Source path exists and is readable
- Output path is writable (if specified)
- At least one scan directory exists
- Namespace mappings are non-empty
- Pipeline steps are valid step names
- Separator is a single character
- Reserved word fixes don't create new conflicts

Validation errors must be clear and actionable:

```
ERROR: Source path "/path/to/project" does not exist.
ERROR: Namespace mapping "/include/" points to a directory that doesn't exist in the source.
WARNING: No transforms defined. Class names will only have underscores replaced with namespace separators.
```

---

## smart:init Auto-Detection Logic

When running `smart:init /path/to/project`, the tool should:

1. Scan for PHP files recursively
2. Count files, total lines, class definitions
3. Detect naming patterns (underscore-separated? Prefix-based? Mixed?)
4. Count include/require statements
5. Identify directory structure (is there an `include/`, `lib/`, `src/`?)
6. Check for existing `composer.json`
7. Generate a YAML config with detected values pre-filled
8. Output a summary: "Found X files, Y classes, Z includes. Config written to smart-autoload-converter.yaml"

This makes the tool immediately usable without writing config from scratch.
