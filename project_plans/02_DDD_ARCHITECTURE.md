# 02 - DDD Architecture

## Objective

Restructure the flat, trait-heavy codebase into a clean Domain-Driven Design architecture with clear boundaries, single-responsibility classes, and dependency injection.

---

## Current Structure (Legacy)

```
src/
  AutoConverterForPRF/AutoloadConverter/
    AutoloadConverterBuilder.php          (god class: config + orchestration)
    BuilderTraits/
      BuilderCommandsTraits.php           (pipeline execution)
      BuilderDirectionsTrait.php          (direction instantiation)
    Directions/                           (pipeline steps, 7 classes)
      HelperTraits/                       (6 traits mixed into directions)
        SearchAndReplace/                 (2 traits)
      DirectionOperations/                (4 operation classes)
    Helpers/
      ClassOperations/                    (6 classes)
      CopyFolderToSelectedFolder.php
      FileContentAnalysis.php
      RegexDefinitions.php
  Controller/
    MigrationController.php               (config + HTTP trigger, 400+ lines)
    PanelController.php
  Helper/
    ClassStringsForPRF.php                (70+ entry mapping, project-specific)
    ClassStrings.php
    Helpers.php                           (global functions)
  CustomLogger/                           (custom logging, 5 files)
  GlobalTraits/                           (4 shared traits)
```

### Problems with Current Structure

1. **Traits everywhere**: 10 traits mixed into classes, making testing impossible
2. **God class**: `AutoloadConverterBuilder` handles config, pipeline, and state
3. **Static state**: Global helper functions, static calls
4. **No interfaces**: Only `DirectionsInterface` exists
5. **Hardcoded config**: `MigrationController` has 400+ lines of project-specific config
6. **No DI**: Classes instantiate their own dependencies with `new`
7. **No tests**: Zero test files

---

## New Structure (DDD)

```
src/
  Domain/
    Analysis/
      Service/
        ClassAnalyzer.php                 Scans files, finds class definitions
        IncludeRequireAnalyzer.php        Finds all include/require statements
        DependencyGraphBuilder.php        Builds class dependency graph
        UnusedClassDetector.php           Finds classes never referenced
      Model/
        ClassDefinition.php               Value Object: name, file, namespace, type
        IncludeStatement.php              Value Object: path, type, line number
        FileAnalysis.php                  Value Object: file path, classes, includes
        DependencyGraph.php               Aggregate: class -> dependencies map
      Exception/
        AnalysisException.php

    Conversion/
      Service/
        NamespaceGenerator.php            Generates PSR-4 namespace from class name
        ClassNameTransformer.php          Underscore -> namespace (configurable rules)
        ReservedWordFixer.php             Abstract -> Abstracts, List -> Lists
        IncludeRemover.php                Removes/updates include statements
        ClassReferenceReplacer.php        Updates all usages (new, extends, etc.)
        SameNameResolver.php              Handles duplicate class names
      Model/
        ConversionRule.php                Value Object: old name -> new name + namespace
        ConversionResult.php              Value Object: file, changes applied
        NamespaceMapping.php              Value Object: directory -> namespace root
      Exception/
        ConversionException.php

    FileSystem/
      Service/
        FileScanner.php                   Recursive PHP file discovery
        FileReader.php                    Read file content with encoding handling
        FileWriter.php                    Write with backup, atomic writes
        DirectoryManager.php              Create/move/delete directories
        BackupManager.php                 Zip or git-based backup
      Model/
        ScannedFile.php                   Value Object: path, size, hash
      Exception/
        FileSystemException.php

    Pipeline/
      Service/
        Pipeline.php                      Executes steps in order
      Model/
        PipelineResult.php                Aggregate: all step results
        StepResult.php                    Value Object: step name, status, changes
      Contract/
        PipelineStepInterface.php         Interface for all steps
      Step/
        AnalyzeStep.php                   Step 1: scan and analyze
        BackupStep.php                    Step 2: create backup
        RemoveIncludesStep.php            Step 3: remove/update includes
        ReplaceReferencesStep.php         Step 4: update class references
        RenameFilesStep.php               Step 5: move files to PSR-4 paths
        AddNamespacesStep.php             Step 6: add namespace declarations
        GenerateComposerStep.php          Step 7: update composer.json autoload
        ComposerDumpStep.php              Step 8: run composer dump-autoload

    Regex/
      ClassUsagePatterns.php              10 regex patterns (new, extends, etc.)
      IncludeRequirePatterns.php          13 include/require variations
      ClassDefinitionPatterns.php         Class/interface/trait detection

    Report/
      Service/
        ReportGenerator.php               Generates conversion report
      Model/
        ConversionReport.php              Full report with statistics
      Exporter/
        JsonExporter.php
        HtmlExporter.php
        ConsoleExporter.php               Table output for CLI

  Application/
    Service/
      ConversionOrchestrator.php          Main entry point, wires everything
      ConfigurationLoader.php             Loads and validates YAML config
      ProjectValidator.php                Validates source project before conversion
      ProjectAnalyzer.php                 Auto-detects patterns for smart:init command
    Command/
      ConvertCommand.php                  Main CLI command (smart:convert)
      AnalyzeCommand.php                  Analysis only (smart:analyze)
      ReportCommand.php                   Generate report (smart:report)
      InitCommand.php                     Generate example config (smart:init)
    Controller/
      ConversionController.php            Optional web UI (future)

  Infrastructure/
    Composer/
      ComposerJsonEditor.php              Edit composer.json autoload section
      ComposerDumper.php                  Run composer dump-autoload
    Git/
      GitCommitter.php                    Auto-commit after each step (optional)
    Logger/
      ConversionLogger.php                Structured logging for the process

bin/
  smart-autoload-converter                Symfony Console Application entry point

config/
  services.yaml                           Symfony DI configuration
  smart_autoload_converter.yaml           Default conversion config

tests/
  (See 06_TESTING_STRATEGY.md for complete test structure)

fixtures/
  legacy-sample-project/
    include/
      MyLib/
        User/
          MyLib_user_construct.php        class MyLib_user_construct
          MyLib_user_list.php             class MyLib_user_list
        Market/
          MyLib_market_construct.php      class MyLib_market_construct
          MyLib_market_view.php           class MyLib_market_view
        UI/
          MyLib_UI_Abstract.php           class MyLib_UI_Abstract (reserved word)
          viewController.php             class viewController (same name in 2 dirs)
      Vendor/
        Vendor_db_adapter.php             class Vendor_db_adapter
        Vendor_db_Abstract.php            class Vendor_db_Abstract (reserved word)
    pages/
      dashboard.php                       has 5 include_once, uses new MyLib_user_construct
      admin/
        users.php                         has require_once, extends MyLib_user_list
        viewController.php               class viewController (SAME NAME as UI one)
    scripts/
      process.php                         has include, instanceof check, static call
      helpers.php                         non-class file (functions), should NOT be removed
    config.php                            non-class include, should be preserved
    index.php                             entry point with multiple requires
    composer.json                         existing composer.json to update
    index.php                             (entry point with requires)
```

---

## Class Mapping: Old to New

| Old Class | New Class | Layer |
|-----------|-----------|-------|
| `AutoloadConverterBuilder` | `ConversionOrchestrator` | Application |
| `BuilderCommandsTraits` | `Pipeline` | Domain/Pipeline |
| `AnalyzeOfFiles` | `ClassAnalyzer` + `IncludeRequireAnalyzer` | Domain/Analysis |
| `SearchAndReplaceInFiles` | `ClassReferenceReplacer` | Domain/Conversion |
| `ChangeFiles` | `RenameFilesStep` + `AddNamespacesStep` | Domain/Pipeline/Step |
| `RemoveOrChangeRequireIncludeRows` | `IncludeRemover` + `RemoveIncludesStep` | Domain/Conversion + Pipeline |
| `ChangeFileContentsAfterCreating` | `ClassReferenceReplacer` (integrated) | Domain/Conversion |
| `ZipWholeFolder` | `BackupManager` | Domain/FileSystem |
| `ClassStringsForPRF` | `ClassNameTransformer` | Domain/Conversion |
| `ClassStrings` | Merged into `ClassNameTransformer` (string utilities) | Domain/Conversion |
| `ClassInformationObject` | `ClassDefinition` | Domain/Analysis/Model |
| `FileObject` | `ScannedFile` | Domain/FileSystem/Model |
| `FileContentAnalysis` | `FileAnalysis` + `ClassAnalyzer` | Domain/Analysis |
| `RegexDefinitions` | `ClassUsagePatterns` + `IncludeRequirePatterns` | Domain/Regex |
| `InvestigateMoreSameClassname` | `SameNameResolver` | Domain/Conversion |
| `CopyFolderToSelectedFolder` | `BackupManager` | Domain/FileSystem |
| `MigrationController` | `ConvertCommand` + `ConfigurationLoader` | Application |
| `Helpers.php` (global functions) | `ReservedWordFixer` (namespaceFix) + merged into `ClassNameTransformer` (string ops) | Domain/Conversion |
| `CustomLogger` | `ConversionLogger` (PSR-3) | Infrastructure |

---

## Design Principles

1. **No traits**: Every trait becomes a service class with dependency injection
2. **No static methods**: All services are injectable
3. **No god classes**: Single responsibility per class
4. **Interfaces for contracts**: `PipelineStepInterface`, repository interfaces
5. **Value Objects for data**: `ClassDefinition`, `IncludeStatement`, `ConversionRule` are immutable
6. **Configuration over convention**: All behavior driven by YAML config
7. **Testable by design**: Every domain service testable in isolation
