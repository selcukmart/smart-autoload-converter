# 05 - Pipeline Design

## Objective

Transform the hardcoded 9-step pipeline into a configurable, extensible step-based system where each step is an independent, testable unit.

---

## Pipeline Architecture

```
ConfigurationLoader
        |
        v
ConversionOrchestrator
        |
        v
Pipeline::execute(steps[], config, dryRun)
        |
        +---> AnalyzeStep
        +---> BackupStep
        +---> RemoveIncludesStep
        +---> ReplaceReferencesStep
        +---> RenameFilesStep
        +---> AddNamespacesStep
        +---> GenerateComposerStep
        +---> ComposerDumpStep
        |
        v
PipelineResult (collection of StepResults)
        |
        v
ReportGenerator
```

---

## PipelineStepInterface

```php
interface PipelineStepInterface
{
    public function getName(): string;
    
    public function execute(
        PipelineContext $context,
        bool $dryRun = false,
    ): StepResult;
    
    public function supports(Configuration $config): bool;
    
    public function getPriority(): int;  // execution order
}
```

---

## Step Details

### Step 1: AnalyzeStep (Priority: 100)

**Input:** Source directory path
**Output:** `DependencyGraph` with all class definitions and include statements
**Process:**
1. `FileScanner` finds all PHP files (respecting ignore patterns)
2. `ClassAnalyzer` parses each file for class/interface/trait definitions
3. `IncludeRequireAnalyzer` finds all include/require statements
4. `DependencyGraphBuilder` maps which files use which classes
5. `SameNameResolver` identifies ambiguous class names
6. `UnusedClassDetector` finds classes never referenced

**Dry-run behavior:** Full analysis, outputs report, no file changes.

### Step 2: BackupStep (Priority: 200)

**Input:** Source directory
**Output:** Backup archive
**Process:**
1. If strategy=zip: create timestamped zip archive
2. If strategy=git: git init + git add + git commit
3. If strategy=copy: full directory copy
4. Verify backup integrity (file count match)

**Dry-run behavior:** Reports what would be backed up, estimated size.

### Step 3: RemoveIncludesStep (Priority: 300)

**Input:** DependencyGraph + file contents
**Output:** Modified files with includes removed/updated
**Process:**
1. For each file with include/require statements:
2. Check if the included file contains a class definition
3. If yes: remove the include line (autoloading will handle it)
4. If no: update the path if directory structure changes
5. If in preserve list: leave untouched
6. Handle edge cases: dynamic includes, variable paths (mark for manual review)

**Dry-run behavior:** Lists includes to remove, includes to update, includes flagged for manual review.

### Step 4: ReplaceReferencesStep (Priority: 400)

**Input:** DependencyGraph + ConversionRules
**Output:** Modified files with all class references updated
**Process:**
1. For each class in the conversion map:
2. Apply "hide and seek" protection (mask class definition line)
3. Replace all 10 usage patterns: new, ::, extends, implements, instanceof, catch, function parameter types, return types, property types, use statements
4. Restore class definition line
5. Track all replacements for the report

**The 10 regex patterns (from ClassUsagePatterns):**
- `new ClassName` -> `new \Namespace\ClassName`
- `ClassName::` -> `\Namespace\ClassName::`
- `extends ClassName` -> `extends \Namespace\ClassName`
- `implements ClassName` -> `implements \Namespace\ClassName`
- `instanceof ClassName` -> `instanceof \Namespace\ClassName`
- `catch (ClassName` -> `catch (\Namespace\ClassName`
- Function param: `(ClassName $var)` -> `(\Namespace\ClassName $var)`
- Return type: `): ClassName` -> `): \Namespace\ClassName`
- Property type: `ClassName $prop` -> `\Namespace\ClassName $prop`
- Use statement: add `use \Namespace\ClassName;` at top of file

**Dry-run behavior:** Lists all replacements per file without applying.

### Step 5: RenameFilesStep (Priority: 500)

**Input:** ConversionRules + file system
**Output:** Files moved to PSR-4 directory structure
**Process:**
1. For each class, compute the PSR-4 file path from its namespace
2. Create target directories
3. Move files to new locations
4. Handle same-name collisions (prefix with parent directory name)

**Dry-run behavior:** Lists source -> target file moves.

### Step 6: AddNamespacesStep (Priority: 600)

**Input:** ConversionRules + moved files
**Output:** Files with namespace declarations added
**Process:**
1. For each moved file, add `namespace X\Y\Z;` after `<?php` / `declare(strict_types=1);`
2. Add `use` statements for referenced classes
3. Apply reserved word fixes (Abstract -> Abstracts)

**Dry-run behavior:** Shows namespace to be added per file.

### Step 7: GenerateComposerStep (Priority: 700)

**Input:** Configuration namespace mappings
**Output:** Updated composer.json
**Process:**
1. Read existing composer.json (or create new)
2. Add PSR-4 autoload entries from config
3. Remove classmap entries that are now PSR-4
4. Write updated composer.json

**Dry-run behavior:** Shows diff of composer.json changes.

### Step 8: ComposerDumpStep (Priority: 800)

**Input:** composer.json
**Output:** vendor/autoload.php regenerated
**Process:**
1. Run `composer dump-autoload --optimize`
2. Verify autoload works (require autoload.php, check class_exists for a few classes)

**Dry-run behavior:** Skipped entirely.

---

## Error Handling

Each step must:

1. Catch exceptions and wrap them in `StepResult` with status=Failed
2. Store the error message and affected file
3. Pipeline continues or stops based on configuration (`stop_on_error: true|false`)
4. Failed steps can be retried: `php bin/console smart:convert --retry-from=rename_files`

---

## Events (for extensibility)

```php
// Dispatched at key moments
PipelineStartedEvent
StepStartedEvent(string $stepName)
StepCompletedEvent(string $stepName, StepResult $result)
StepFailedEvent(string $stepName, \Throwable $error)
PipelineCompletedEvent(PipelineResult $result)
FileModifiedEvent(string $filePath, array $changes)
ClassConvertedEvent(string $oldName, string $newName)
```

Users can hook into these events for custom behavior (logging, notifications, custom post-processing).
