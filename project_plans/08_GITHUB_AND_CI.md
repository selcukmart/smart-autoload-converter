# 08 - GitHub and CI Plan

## Objective

Set up a professional open-source GitHub repository with automated CI/CD, release management, and Packagist publication.

---

## Repository Setup

**URL:** github.com/selcukmart/smart-autoload-converter
**License:** MIT
**Default branch:** main
**Branch protection:** Require PR reviews for main

### Labels

| Label | Color | Use |
|-------|-------|-----|
| bug | #d73a4a | Something isn't working |
| enhancement | #a2eeef | New feature |
| documentation | #0075ca | Docs improvement |
| good first issue | #7057ff | Good for newcomers |
| help wanted | #008672 | Extra attention needed |
| pipeline-step | #e4e669 | Related to pipeline steps |
| regex | #fbca04 | Related to regex patterns |
| breaking-change | #b60205 | Introduces breaking changes |

### Issue Templates

- Bug report (with reproduction steps)
- Feature request
- New pipeline step proposal

### PR Template

```markdown
## Description
Brief description of changes.

## Type
- [ ] Bug fix
- [ ] New feature
- [ ] Refactoring
- [ ] Documentation

## Checklist
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] PHPStan passes (level 9)
- [ ] PHP CS Fixer passes
- [ ] All existing tests pass
```

---

## GitHub Actions Workflows

### CI (on every push and PR)

```yaml
# .github/workflows/ci.yml
name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.4', '8.5']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: xdebug
      - run: composer install
      - run: vendor/bin/phpunit --coverage-clover coverage.xml
      - uses: codecov/codecov-action@v4

  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.5'
      - run: composer install
      - run: vendor/bin/phpstan analyze
      - run: vendor/bin/php-cs-fixer fix --dry-run --diff

  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - run: composer audit
```

### Release (on tag push)

```yaml
# .github/workflows/release.yml
name: Release

on:
  push:
    tags: ['v*']

jobs:
  release:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Create GitHub Release
        uses: softprops/action-gh-release@v1
        with:
          generate_release_notes: true
```

---

## Packagist Publication

### composer.json for distribution

```json
{
    "name": "selcukmart/smart-autoload-converter",
    "description": "Convert legacy PHP projects from include/require to PSR-4 autoloading",
    "type": "project",
    "license": "MIT",
    "keywords": ["php", "autoload", "psr-4", "legacy", "migration", "namespace", "modernization"],
    "authors": [
        {
            "name": "Selcuk Mart",
            "homepage": "https://github.com/selcukmart"
        }
    ],
    "require": {
        "php": ">=8.4",
        "symfony/console": "^7.4",
        "symfony/yaml": "^7.4",
        "symfony/finder": "^7.4",
        "symfony/filesystem": "^7.4",
        "symfony/process": "^7.4",
        "symfony/validator": "^7.4"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "phpstan/phpstan": "^2.0",
        "friendsofphp/php-cs-fixer": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "SmartAutoloadConverter\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "SmartAutoloadConverter\\Tests\\": "tests/"
        }
    },
    "bin": ["bin/smart-autoload-converter"]
}
```

### Versioning Strategy

| Version | Meaning |
|---------|---------|
| v0.1.0 | First public release (MVP: analyze + convert) |
| v0.2.0 | Dry-run mode + report output |
| v0.3.0 | smart:init auto-detection |
| v0.4.0 | Git commit per step |
| v1.0.0 | Stable, fully tested, documented |

---

## Badges for README

```markdown
[![CI](https://github.com/selcukmart/smart-autoload-converter/actions/workflows/ci.yml/badge.svg)](...)
[![Coverage](https://codecov.io/gh/selcukmart/smart-autoload-converter/branch/main/graph/badge.svg)](...)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](...)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-blue.svg)](...)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](...)
[![Packagist](https://img.shields.io/packagist/v/selcukmart/smart-autoload-converter.svg)](...)
```
