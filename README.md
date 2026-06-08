# Smart Autoload Converter

> Convert legacy PHP projects from include/require to PSR-4 autoloading automatically.

**Status:** Planning phase. See [project_plans/](project_plans/) for the full project plan.

## Origin

This tool is based on a battle-tested AutoloadConverter that successfully migrated a 1.6 million line legacy PHP codebase (6,669 files, 10,602 include/require statements, 4,087 class definitions) to PSR-4 namespaces.

The original story is documented in a 4-part Medium series: [1.6 Million Lines, Zero Namespaces: A PHP Modernization War Story](https://medium.com/@martselcuk).

## What This Will Do

```php
// BEFORE: legacy include/require spaghetti
require_once dirname(__FILE__) . '/../include/MyLib_user_construct.php';
require_once dirname(__FILE__) . '/../include/MyLib_market_list.php';

$user = new MyLib_user_construct();
$markets = MyLib_market_list::getAll();

// AFTER: PSR-4 autoloading with namespaces
namespace App\Pages;

use MyLib\User\User;
use MyLib\Market\MarketList;

$user = new User();
$markets = MarketList::getAll();
```

## Planned Tech Stack

- **PHP 8.5** with strict types, readonly classes, enums
- **Symfony 7.4** Console commands as primary interface
- **DDD architecture** with clean domain boundaries
- **YAML configuration** for project-specific conversion rules
- **Dry-run mode** for safe preview before conversion
- **Full test coverage** with fixture-based integration tests

## Project Plan

| Document | Content |
|----------|---------|
| [00 Master Plan](project_plans/00_MASTER_PLAN.md) | Vision, phases, success criteria |
| [01 Anonymization](project_plans/01_ANONYMIZATION.md) | Removing proprietary references |
| [02 DDD Architecture](project_plans/02_DDD_ARCHITECTURE.md) | Domain structure, class mapping |
| [03 Modernization](project_plans/03_MODERNIZATION.md) | PHP 8.5, Symfony 7.4, modern patterns |
| [04 CLI and Configuration](project_plans/04_CLI_AND_CONFIGURATION.md) | Commands, YAML schema |
| [05 Pipeline Design](project_plans/05_PIPELINE_DESIGN.md) | 8-step conversion pipeline |
| [06 Testing Strategy](project_plans/06_TESTING_STRATEGY.md) | Unit, integration, fixtures |
| [07 Documentation](project_plans/07_DOCUMENTATION.md) | README, architecture, examples |
| [08 GitHub and CI](project_plans/08_GITHUB_AND_CI.md) | Actions, Packagist, releases |
| [09 Medium Article](project_plans/09_MEDIUM_ARTICLE.md) | Publication plan |
| [10 Open Source Setup](project_plans/10_OPEN_SOURCE_SETUP.md) | License, contributing, CoC, security |

## License

MIT

## Author

Selcuk Mart - [@martselcuk](https://medium.com/@martselcuk)
