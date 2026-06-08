# 07 - Documentation Plan

## Objective

Create comprehensive documentation that makes the tool immediately usable for any PHP developer facing a legacy modernization challenge.

---

## Documentation Structure

```
docs/
  README.md                      Project overview (also root README.md)
  ARCHITECTURE.md                DDD structure, design decisions
  CONFIGURATION.md               Full YAML config reference
  PIPELINE.md                    Step-by-step pipeline explanation
  QUICK_START.md                 5-minute guide to first conversion
  EXAMPLES.md                   Real-world usage examples
  CONTRIBUTING.md                How to contribute
  CHANGELOG.md                   Version history
```

---

## README.md (Root)

Must include:
1. One-sentence description
2. Badges (CI status, coverage, PHP version, license, Packagist)
3. "What it does" with before/after code example
4. Installation (composer require)
5. Quick start (3 commands)
6. Feature list
7. Configuration overview (link to full docs)
8. Comparison: "How is this different from Rector/php-cs-fixer?"
9. Credits and origin story (link to Medium series)
10. License

### Before/After Example (key selling point)

```php
// BEFORE: legacy-project/include/MyLib_user_construct.php
require_once dirname(__FILE__) . '/../MyLib_market_list.php';
require_once dirname(__FILE__) . '/../../config.php';

class MyLib_user_construct {
    public function getMarkets() {
        $list = new MyLib_market_list();
        return $list->getAll();
    }
}

// AFTER: legacy-project/src/MyLib/User/User.php
namespace MyLib\User;

use MyLib\Market\MarketList;

class User {
    public function getMarkets(): array {
        $list = new MarketList();
        return $list->getAll();
    }
}
```

### Differentiation Section

| Tool | Purpose | Smart Autoload Converter |
|------|---------|--------------------------|
| Rector | Automated refactoring of modern PHP | Targets pre-namespace legacy PHP |
| PHP-CS-Fixer | Code style fixing | Does not handle structural migration |
| PhpStorm Refactoring | IDE-based rename/move | Cannot handle 10,000+ files batch |
| Manual migration | Hand-editing files | Not feasible for large codebases |

---

## ARCHITECTURE.md

Content:
1. DDD layer diagram
2. Domain model explanation (ClassDefinition, IncludeStatement, ConversionRule)
3. Pipeline architecture with step interface
4. Regex pattern library explanation
5. Configuration system design
6. Extension points (custom steps, custom transformers)
7. Decision log: why DDD, why no traits, why YAML config

---

## CONFIGURATION.md

Full reference for every YAML key with:
- Description
- Type
- Default value
- Example
- Related command-line flag override

---

## QUICK_START.md

```markdown
# Quick Start

## 1. Install

composer require selcukmart/smart-autoload-converter

## 2. Generate config

php bin/console smart:init /path/to/your/legacy-project

## 3. Review and adjust config

Edit smart-autoload-converter.yaml to match your project's naming conventions.

## 4. Preview changes (dry run)

php bin/console smart:convert --config=smart-autoload-converter.yaml --dry-run

## 5. Convert

php bin/console smart:convert --config=smart-autoload-converter.yaml

## 6. Verify

composer dump-autoload
php -r "require 'vendor/autoload.php'; var_dump(class_exists('YourNamespace\\YourClass'));"
```

---

## EXAMPLES.md

Three real-world scenarios:

### Example 1: Simple underscore-separated library
Small project, 50 files, `Lib_module_class` naming.

### Example 2: Zend Framework 1 style
`Zend_Db_Table_Abstract` naming with deep hierarchies.

### Example 3: Mixed conventions
Some files with namespaces, some without. Partial migration scenario.

Each example includes the config YAML, command output, and before/after directory structure.
