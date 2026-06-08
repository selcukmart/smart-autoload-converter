# 01 - Anonymization Plan

## Objective

Remove every reference to the original company, project, and proprietary configuration. The resulting codebase must contain zero traces of its origin.

---

## References to Remove

### Company/Project Names

| Find | Replace With | Locations |
|------|-------------|-----------|
| `PRF` (as prefix/namespace) | `Legacy` or configurable | Class names, namespaces, comments |
| `prf` (lowercase) | `legacy` | Variables, config keys, file paths |
| `profiflitzer` / `Profiflitzer` | Remove entirely | Comments, strings, paths |
| `PRFLIB` | `LegacyLib` or remove | Directory references, comments |
| `FLZR` / `flzr` | Remove entirely | Any remaining references |
| `my-profiflitzer` | `legacy-project` | Path examples, comments |
| `my-profiflitzer.local` | `legacy-project.local` | Config examples |
| `AutoConverterForPRF` | `SmartAutoloadConverter` | Root namespace |
| `ClassStringsForPRF` | `ClassNameTransformer` | Class name |
| `Head Of IT` | `Selcuk Mart` or remove | Git author (already clean, new repo) |
| `hoit@profiflitzer.de` | Remove | Email references |
| `hostingdevi.com` | Remove | Author annotations |

### Hardcoded Paths in MigrationController

The `MigrationController.php` contains project-specific configuration:

```php
'class_change_dirs' => ['/my-profiflitzer.local', '/scripts', '/Zend', '/apigility_prf']
'main_library_areas' => ['/my-profiflitzer.local/include/' => '', '/Zend' => 'Zend']
'ignore' => ['backup-27-12-2020', 'backup-03-11-2022', ...]
```

All of this becomes YAML configuration. No hardcoded paths in code.

### The `covert_to` Map (70+ entries)

`ClassStringsForPRF.php` contains a 70+ entry mapping specific to the original project:

```php
private array $covert_to = [
    'construct' => '',
    'list' => 'List',
    'view' => 'View',
    // ...
];
```

This becomes a YAML configuration section that users define for their own projects.

---

## Files Requiring Changes

### Must Rename (namespace/class name changes)

| Current Path | New Path |
|-------------|----------|
| `src/AutoConverterForPRF/` | `src/Domain/` (DDD restructure) |
| `src/Helper/ClassStringsForPRF.php` | `src/Domain/Conversion/ClassNameTransformer.php` |
| `src/Helper/ClassStrings.php` | `src/Domain/Conversion/ClassStringUtils.php` |
| `src/Controller/MigrationController.php` | `src/Application/Controller/ConversionController.php` |
| `src/Controller/PanelController.php` | Remove (web UI rebuilt in Phase 6) |

### Must Edit (remove PRF references in content)

All 51 PHP files contain at least one PRF reference (namespace, comment, or variable name). Every file needs content editing after the structural move.

### Must Delete

| Path | Reason |
|------|--------|
| `var/` | Cache artifacts with PRF references |
| `vendor/` | Will be regenerated |
| `.env` | May contain project-specific values |
| `templates/` | Twig templates specific to old web UI |

---

## Verification Checklist

After anonymization, run:

```bash
grep -rn "PRF\|prf\|profiflitzer\|Profiflitzer\|PRFLIB\|flzr\|FLZR\|hostingdevi\|hoit@" src/ config/ docs/ tests/
```

Expected output: zero matches.
