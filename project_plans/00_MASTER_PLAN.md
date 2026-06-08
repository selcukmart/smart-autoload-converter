# Smart Autoload Converter - Master Project Plan

**Project:** Smart Autoload Converter
**Author:** Selcuk Mart
**Version:** 1.0
**Date:** June 2026
**License:** MIT (Open Source)
**Repository:** github.com/selcukmart/smart-autoload-converter

---

## Vision

Transform the battle-tested AutoloadConverter (originally built in 2022 to migrate a 1.6M-line legacy PHP codebase) into a generic, open-source tool that any developer can use to migrate legacy PHP projects from include/require to PSR-4 autoloading.

## Origin

This tool was originally built for a specific project (documented in the Medium series "1.6 Million Lines, Zero Namespaces: A PHP Modernization War Story"). It successfully converted 6,669 PHP files, 10,602 include/require statements, and 4,087 class definitions to PSR-4 namespaces in 2 months. The tool itself was 5,226 lines of PHP across 51 files.

This project takes that proven codebase and makes it:
- Generic (works on any legacy PHP project, not just one)
- Modern (PHP 8.5, Symfony 7.4, DDD architecture)
- Tested (full unit and integration test coverage)
- Configurable (YAML-based, no hardcoded project paths)
- Open Source (MIT license, public GitHub repo)

---

## Project Plan Documents

| # | Document | Purpose |
|---|----------|---------|
| 00 | [Master Plan](./00_MASTER_PLAN.md) | This document |
| 01 | [Anonymization](./01_ANONYMIZATION.md) | Remove all company-specific references |
| 02 | [DDD Architecture](./02_DDD_ARCHITECTURE.md) | New domain-driven structure with class mapping |
| 03 | [Modernization](./03_MODERNIZATION.md) | PHP 8.5, Symfony 7.4, modern patterns |
| 04 | [CLI and Configuration](./04_CLI_AND_CONFIGURATION.md) | Symfony Commands, YAML config system |
| 05 | [Pipeline Design](./05_PIPELINE_DESIGN.md) | The conversion pipeline steps and extensibility |
| 06 | [Testing Strategy](./06_TESTING_STRATEGY.md) | Unit tests, integration tests, fixtures |
| 07 | [Documentation](./07_DOCUMENTATION.md) | README, architecture docs, usage examples |
| 08 | [GitHub and CI](./08_GITHUB_AND_CI.md) | GitHub Actions, release strategy, Packagist |
| 09 | [Medium Article](./09_MEDIUM_ARTICLE.md) | Publication plan for the open-source announcement |

---

## Implementation Phases

| Phase | Title | Estimated Duration |
|-------|-------|--------------------|
| 1 | Anonymization + cleanup | 1 session |
| 2 | DDD restructure + empty classes | 1 session |
| 3 | Domain layer: Analysis + Conversion + Pipeline | 2-3 sessions |
| 4 | Application layer: Services + CLI Commands | 1 session |
| 5 | Unit tests with fixtures | 1-2 sessions |
| 6 | Integration test (full conversion of fixture project) | 1 session |
| 7 | Documentation + README + GitHub Actions CI | 1 session |
| 8 | GitHub public release + Medium article | 1 session |

---

## Success Criteria

- [ ] Zero references to original company/project names
- [ ] Full DDD architecture with clear domain boundaries
- [ ] PHP 8.5 strict types, readonly properties, enums
- [ ] Symfony 7.4 with console commands as primary interface
- [ ] YAML-based configuration (no hardcoded paths)
- [ ] Dry-run mode for safe preview
- [ ] JSON/HTML report output
- [ ] Unit test coverage > 90%
- [ ] Integration test with fixture legacy project
- [ ] GitHub Actions CI (test, lint, build)
- [ ] Packagist publication (composer require selcukmart/smart-autoload-converter)
- [ ] Medium article published
- [ ] README with clear usage instructions and examples
