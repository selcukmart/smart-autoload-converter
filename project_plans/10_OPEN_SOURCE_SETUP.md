# 10 - Open Source Setup

## Objective

Prepare all community-facing files, governance documents, and repository standards required for a professional open-source PHP project.

---

## Required Files

### Root Directory

| File | Purpose | Status |
|------|---------|--------|
| `LICENSE` | MIT License text | To create |
| `README.md` | Project overview, installation, usage | To create (from 07_DOCUMENTATION plan) |
| `CONTRIBUTING.md` | How to contribute | To create |
| `CODE_OF_CONDUCT.md` | Community behavior standards | To create |
| `CHANGELOG.md` | Version history | To create |
| `SECURITY.md` | Vulnerability reporting policy | To create |
| `.editorconfig` | Cross-IDE formatting consistency | To create |
| `.php-cs-fixer.dist.php` | PHP code style rules | To create |
| `phpstan.neon` | Static analysis config | To create |
| `phpunit.xml.dist` | Test runner config | To create |

### GitHub Directory

| File | Purpose |
|------|---------|
| `.github/ISSUE_TEMPLATE/bug_report.md` | Bug report template |
| `.github/ISSUE_TEMPLATE/feature_request.md` | Feature request template |
| `.github/PULL_REQUEST_TEMPLATE.md` | PR checklist |
| `.github/FUNDING.yml` | Sponsorship links (optional) |
| `.github/workflows/ci.yml` | CI pipeline |
| `.github/workflows/release.yml` | Release automation |

---

## LICENSE (MIT)

```
MIT License

Copyright (c) 2026 Selcuk Mart

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## CONTRIBUTING.md

### Content Structure

```markdown
# Contributing to Smart Autoload Converter

Thank you for your interest in contributing!

## How to Contribute

### Reporting Bugs

- Use the GitHub issue tracker
- Include: PHP version, Symfony version, OS, steps to reproduce
- Include the YAML config you used (remove sensitive paths)
- Include the error message or unexpected behavior

### Suggesting Features

- Open a feature request issue
- Describe the use case (what legacy pattern are you trying to convert?)
- If possible, include a before/after code example

### Submitting Code

1. Fork the repository
2. Create a feature branch from `main` (`git checkout -b feat/your-feature`)
3. Write tests for new functionality
4. Ensure all checks pass:
   - `make test` (PHPUnit)
   - `make lint` (PHPStan level 9)
   - `make cs-fix` (PHP CS Fixer)
5. Commit with conventional messages (feat:, fix:, docs:, test:, refactor:)
6. Push and open a Pull Request

### Adding a New Pipeline Step

Pipeline steps are the most welcome contribution. To add a step:

1. Create a class implementing `PipelineStepInterface` in `src/Domain/Pipeline/Step/`
2. Implement `getName()`, `execute()`, `supports()`, `getPriority()`
3. Write unit tests in `tests/Domain/Pipeline/Step/`
4. Add the step name to the pipeline configuration schema
5. Document the step in `docs/PIPELINE.md`
6. Submit a PR with an example config that uses the step

### Adding a New Regex Pattern

If you find a PHP class usage pattern that the converter misses:

1. Add the pattern to `src/Domain/Regex/ClassUsagePatterns.php`
2. Add test cases to `tests/Domain/Regex/ClassUsagePatternsTest.php`
3. Include the original PHP code that triggered the miss
4. Submit a PR

## Code Standards

- PHP 8.4+ strict types everywhere
- No traits (use service classes with DI)
- No static methods (all services injectable)
- PHPStan level 9 must pass
- All public methods must have doc comments
- All new code must have tests

## Commit Convention

- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation only
- `test:` Adding or updating tests
- `refactor:` Code change that neither fixes a bug nor adds a feature
- `chore:` Maintenance (CI, dependencies, configs)

## Development Setup

Prerequisites: PHP 8.4+, Composer

git clone https://github.com/selcukmart/smart-autoload-converter.git
cd smart-autoload-converter
composer install
make test
```

---

## CODE_OF_CONDUCT.md

Use the Contributor Covenant v2.1 (industry standard):

```markdown
# Contributor Covenant Code of Conduct

## Our Pledge

We as members, contributors, and leaders pledge to make participation in our
community a harassment-free experience for everyone, regardless of age, body
size, visible or invisible disability, ethnicity, sex characteristics, gender
identity and expression, level of experience, education, socio-economic status,
nationality, personal appearance, race, religion, or sexual identity and
orientation.

## Our Standards

Examples of behavior that contributes to a positive environment:

* Using welcoming and inclusive language
* Being respectful of differing viewpoints and experiences
* Gracefully accepting constructive criticism
* Focusing on what is best for the community
* Showing empathy towards other community members

Examples of unacceptable behavior:

* Trolling, insulting/derogatory comments, and personal or political attacks
* Public or private harassment
* Publishing others' private information without explicit permission
* Other conduct which could reasonably be considered inappropriate

## Enforcement

Instances of abusive behavior may be reported to the project maintainer
at [INSERT EMAIL]. All complaints will be reviewed and investigated.

## Attribution

This Code of Conduct is adapted from the Contributor Covenant, version 2.1,
available at https://www.contributor-covenant.org/version/2/1/code_of_conduct.html
```

---

## SECURITY.md

```markdown
# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability in Smart Autoload Converter, please
report it responsibly.

**Do NOT open a public GitHub issue for security vulnerabilities.**

Instead, please email: [INSERT EMAIL]

You will receive a response within 48 hours acknowledging your report.

## Scope

Smart Autoload Converter is a CLI tool that operates on local files. Security
concerns include:

- Path traversal (processing files outside the configured source directory)
- Command injection (if user config values are passed to shell commands)
- Arbitrary file write (writing to locations outside the output directory)

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.x     | Yes       |
| 0.x     | Best effort |
```

---

## CHANGELOG.md

```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Initial project structure with DDD architecture
- 8-step conversion pipeline
- YAML-based configuration system
- CLI commands: smart:convert, smart:analyze, smart:init, smart:report
- Dry-run mode for safe preview
- JSON and HTML report export
- Fixture legacy project for testing
- GitHub Actions CI/CD
- PHPStan level 9
- PHP CS Fixer configuration

## [0.1.0] - TBD

### Added
- First public release
- Core conversion pipeline (analyze, backup, convert, namespace)
- 10 regex patterns for class usage detection
- 13 include/require pattern variations
- Reserved word fixer (Abstract, Interface, Trait, Class, List)
- Same-name class resolution
- Configurable class name transformations
- Composer.json autoload section generation
```

---

## .editorconfig

```ini
root = true

[*]
charset = utf-8
end_of_line = lf
insert_final_newline = true
trim_trailing_whitespace = true
indent_style = space
indent_size = 4

[*.md]
trim_trailing_whitespace = false

[*.yml]
indent_size = 2

[*.yaml]
indent_size = 2

[Makefile]
indent_style = tab
```

---

## GitHub Issue Templates

### Bug Report

```markdown
---
name: Bug Report
about: Report a bug in the conversion process
labels: bug
---

## Description
A clear description of the bug.

## Steps to Reproduce
1. Created config with...
2. Ran `smart:convert --config=...`
3. Observed...

## Expected Behavior
What should have happened.

## Actual Behavior
What actually happened.

## Environment
- PHP version:
- Symfony version:
- OS:
- Smart Autoload Converter version:

## Configuration (YAML)
```yaml
# paste your config here (remove sensitive paths)
```

## Error Output
```
# paste the error output here
```
```

### Feature Request

```markdown
---
name: Feature Request
about: Suggest a new feature or improvement
labels: enhancement
---

## Problem
What legacy PHP pattern can't you convert with the current tool?

## Proposed Solution
How should the tool handle this pattern?

## Example Code

### Before (legacy)
```php
// paste legacy code here
```

### Expected After (converted)
```php
// paste expected result here
```

## Alternatives Considered
Other approaches you've thought about.
```

---

## Pre-Release Checklist

Before tagging v0.1.0:

- [ ] All PRF/company references removed (grep verification passes)
- [ ] LICENSE file present (MIT)
- [ ] README.md complete with badges, installation, quick start, before/after
- [ ] CONTRIBUTING.md present
- [ ] CODE_OF_CONDUCT.md present
- [ ] SECURITY.md present
- [ ] CHANGELOG.md present with v0.1.0 entries
- [ ] .editorconfig present
- [ ] .php-cs-fixer.dist.php present and passing
- [ ] phpstan.neon present, level 9, no errors
- [ ] phpunit.xml.dist present, all tests green
- [ ] .github/workflows/ci.yml present and passing
- [ ] .github/ISSUE_TEMPLATE/ present (bug + feature)
- [ ] .github/PULL_REQUEST_TEMPLATE.md present
- [ ] Packagist account ready
- [ ] composer.json has correct name, description, keywords, license
- [ ] bin/smart-autoload-converter executable works
- [ ] Fixture project converts successfully
- [ ] No hardcoded paths in any source file
