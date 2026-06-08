# 06 - Testing Strategy

## Objective

Achieve comprehensive test coverage with unit tests, integration tests, and a fixture-based end-to-end test that converts a sample legacy project.

---

## Test Structure

```
tests/
  Domain/
    Analysis/
      ClassAnalyzerTest.php
      IncludeRequireAnalyzerTest.php
      DependencyGraphBuilderTest.php
      UnusedClassDetectorTest.php
    Conversion/
      NamespaceGeneratorTest.php
      ClassNameTransformerTest.php
      ReservedWordFixerTest.php
      IncludeRemoverTest.php
      ClassReferenceReplacerTest.php
      SameNameResolverTest.php
    FileSystem/
      FileScannerTest.php
      BackupManagerTest.php
    Pipeline/
      PipelineTest.php
      Step/
        AnalyzeStepTest.php
        RemoveIncludesStepTest.php
        ReplaceReferencesStepTest.php
        RenameFilesStepTest.php
        AddNamespacesStepTest.php
    Regex/
      ClassUsagePatternsTest.php
      IncludeRequirePatternsTest.php
    Report/
      ReportGeneratorTest.php
  Application/
    Service/
      ConversionOrchestratorTest.php
      ConfigurationLoaderTest.php
    Command/
      ConvertCommandTest.php
      AnalyzeCommandTest.php
      InitCommandTest.php
  Integration/
    FullConversionTest.php
```

---

## Fixture Legacy Project

A minimal but representative legacy PHP project that exercises all conversion features:

```
fixtures/legacy-sample-project/
  include/
    MyLib/
      User/
        MyLib_user_construct.php        # class MyLib_user_construct
        MyLib_user_list.php             # class MyLib_user_list
      Market/
        MyLib_market_construct.php      # class MyLib_market_construct
        MyLib_market_view.php           # class MyLib_market_view
      UI/
        MyLib_UI_Abstract.php           # class MyLib_UI_Abstract (reserved word!)
        viewController.php              # class viewController (same name in 2 dirs)
    Vendor/
      Vendor_db_adapter.php             # class Vendor_db_adapter
      Vendor_db_Abstract.php            # class Vendor_db_Abstract (reserved word!)
  pages/
    dashboard.php                       # has 5 include_once, uses new MyLib_user_construct
    admin/
      users.php                         # has require_once, extends MyLib_user_list
      viewController.php                # class viewController (SAME NAME as UI one!)
  scripts/
    process.php                         # has include, instanceof check, static call
    helpers.php                         # non-class file (functions), should NOT be removed
  config.php                            # non-class include, should be preserved
  index.php                             # entry point with multiple requires
  composer.json                         # existing composer.json to update
```

### What This Fixture Tests

| Feature | Fixture File | Test |
|---------|-------------|------|
| Underscore to namespace | `MyLib_user_construct.php` | `MyLib\User` |
| Suffix transformation | `_construct` suffix dropped | `MyLib\User` (not `MyLib\User\Construct`) |
| Reserved word fix | `MyLib_UI_Abstract.php` | `MyLib\UI\Abstracts` |
| Same-name resolution | `viewController.php` (2 locations) | `UI\ViewController` vs `Admin\ViewController` |
| Include removal (class) | `dashboard.php` includes class files | Includes removed |
| Include preservation (non-class) | `config.php` included in `index.php` | Include stays, path updated |
| 10 regex patterns | `process.php` uses new, ::, extends, instanceof | All references updated |
| Composer.json update | `composer.json` exists | PSR-4 autoload added |

---

## Unit Test Examples

### ClassNameTransformerTest

```php
class ClassNameTransformerTest extends TestCase
{
    public function testUnderscoreToNamespace(): void
    {
        $transformer = new ClassNameTransformer($config);
        $result = $transformer->transform('MyLib_user_construct');
        
        $this->assertEquals('MyLib\\User', $result->fullyQualifiedName);
        $this->assertEquals('User', $result->className);
        $this->assertEquals('MyLib', $result->namespace);
    }

    public function testReservedWordFix(): void
    {
        $result = $transformer->transform('MyLib_UI_Abstract');
        $this->assertEquals('MyLib\\UI\\Abstracts', $result->fullyQualifiedName);
    }

    public function testCustomSuffixMapping(): void
    {
        $result = $transformer->transform('MyLib_user_list');
        $this->assertEquals('MyLib\\User\\UserList', $result->fullyQualifiedName);
    }
}
```

### ClassUsagePatternsTest

```php
class ClassUsagePatternsTest extends TestCase
{
    /**
     * @dataProvider usagePatternProvider
     */
    public function testPatternDetection(string $code, string $expectedClass): void
    {
        $patterns = new ClassUsagePatterns();
        $found = $patterns->findClassUsages($code);
        
        $this->assertContains($expectedClass, $found);
    }

    public static function usagePatternProvider(): array
    {
        return [
            'new keyword' => ['$obj = new MyLib_user_construct();', 'MyLib_user_construct'],
            'static call' => ['MyLib_user_construct::create();', 'MyLib_user_construct'],
            'extends' => ['class Admin extends MyLib_user_construct', 'MyLib_user_construct'],
            'implements' => ['class Admin implements MyLib_UI_Interface', 'MyLib_UI_Interface'],
            'instanceof' => ['if ($obj instanceof MyLib_user_construct)', 'MyLib_user_construct'],
            'catch' => ['} catch (MyLib_Exception $e) {', 'MyLib_Exception'],
            'param type' => ['function process(MyLib_user_construct $user)', 'MyLib_user_construct'],
            'return type' => ['function get(): MyLib_user_construct', 'MyLib_user_construct'],
        ];
    }
}
```

### IncludeRequirePatternsTest

```php
class IncludeRequirePatternsTest extends TestCase
{
    /**
     * @dataProvider includePatternProvider
     */
    public function testIncludeDetection(string $line, string $expectedPath): void
    {
        $patterns = new IncludeRequirePatterns();
        $result = $patterns->parse($line);
        
        $this->assertNotNull($result);
        $this->assertEquals($expectedPath, $result->path);
    }

    public static function includePatternProvider(): array
    {
        return [
            'require_once double quotes' => ["require_once \"../include/file.php\";", '../include/file.php'],
            'require_once single quotes' => ["require_once '../include/file.php';", '../include/file.php'],
            'include with parens' => ["include('../lib/helper.php');", '../lib/helper.php'],
            'require with dirname' => ["require dirname(__FILE__) . '/../lib/file.php';", 'dirname_relative'],
            'include_once __DIR__' => ["include_once __DIR__ . '/config.php';", '__DIR__/config.php'],
        ];
    }
}
```

---

## Integration Test

### FullConversionTest

```php
class FullConversionTest extends KernelTestCase
{
    private string $fixtureDir;
    private string $workDir;

    protected function setUp(): void
    {
        // Copy fixture to temp directory
        $this->fixtureDir = __DIR__ . '/../../fixtures/legacy-sample-project';
        $this->workDir = sys_get_temp_dir() . '/smart-autoload-test-' . uniqid();
        $this->copyDirectory($this->fixtureDir, $this->workDir);
    }

    public function testFullConversion(): void
    {
        $orchestrator = self::getContainer()->get(ConversionOrchestrator::class);
        $result = $orchestrator->convert($this->workDir, $config);
        
        // Verify: all steps completed
        $this->assertTrue($result->isSuccessful());
        
        // Verify: namespaces added
        $content = file_get_contents($this->workDir . '/include/MyLib/User/User.php');
        $this->assertStringContains('namespace MyLib\\User;', $content);
        
        // Verify: includes removed
        $dashboard = file_get_contents($this->workDir . '/pages/dashboard.php');
        $this->assertStringNotContains('require_once', $dashboard);
        
        // Verify: composer.json updated
        $composer = json_decode(file_get_contents($this->workDir . '/composer.json'), true);
        $this->assertArrayHasKey('MyLib\\', $composer['autoload']['psr-4']);
        
        // Verify: autoloading works
        require $this->workDir . '/vendor/autoload.php';
        $this->assertTrue(class_exists('MyLib\\User'));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workDir);
    }
}
```

---

## Coverage Target

| Layer | Target | Rationale |
|-------|--------|-----------|
| Domain/Regex | 100% | Critical, regex bugs break everything |
| Domain/Conversion | 95% | Core logic |
| Domain/Analysis | 90% | File I/O edge cases hard to cover |
| Domain/Pipeline | 90% | Step orchestration |
| Application/Command | 80% | CLI I/O testing is verbose |
| Integration | 1 full test | Proves the whole system works |
| **Overall** | **> 90%** | |
