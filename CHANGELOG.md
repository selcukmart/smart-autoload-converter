# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2026-06-09

### Added
- Complete DDD architecture: Domain, Application, Infrastructure layers
- 9-step conversion pipeline (analyze → backup → prepare_output → remove_includes → replace_references → rename_files → add_namespaces → generate_composer → composer_dump)
- Standalone CLI: `vendor/bin/smart-autoload-converter`
- 4 commands: `analyze`, `convert`, `init`, `report`
- Dry-run mode with full pipeline preview
- Source directory protection (PrepareOutputStep copies to output first)
- 10 regex patterns for class usage detection
- Reserved word handling (Abstract → Abstracts, Interface → Interfaces, etc.)
- Duplicate class name detection
- Console, JSON, and HTML report exporters
- YAML configuration support
- Docker development environment with Makefile
- 82 unit + integration tests
- Sample legacy project in fixtures/
- GitHub Actions CI

### Origin
- Extracted from a production tool that converted 6,669 PHP files in a 1.6M-line legacy codebase
- Rewritten from scratch with DDD, PHP 8.5, Symfony 7.4 LTS
