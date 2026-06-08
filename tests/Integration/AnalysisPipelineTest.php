<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Tests\Integration;

use SmartAutoloadConverter\Domain\Analysis\Service\ClassAnalyzer;
use SmartAutoloadConverter\Domain\Analysis\Service\DependencyGraphBuilder;
use SmartAutoloadConverter\Domain\Conversion\Service\ClassNameTransformer;
use SmartAutoloadConverter\Domain\FileSystem\Service\FileScanner;
use SmartAutoloadConverter\Domain\FileSystem\Service\FileWriter;
use SmartAutoloadConverter\Domain\Regex\ClassUsagePatterns;
use SmartAutoloadConverter\Domain\Regex\IncludeRequirePatterns;
use PHPUnit\Framework\TestCase;

/**
 * Integration test: runs analysis pipeline on the fixture legacy project.
 * Verifies class detection, include detection, and conversion rule generation.
 */
class AnalysisPipelineTest extends TestCase
{
    private string $fixturesPath;
    private FileScanner $fileScanner;
    private DependencyGraphBuilder $graphBuilder;
    private ClassNameTransformer $transformer;

    protected function setUp(): void
    {
        $this->fixturesPath = dirname(__DIR__, 2) . '/fixtures/legacy-sample-project';
        $this->fileScanner = new FileScanner();

        $usagePatterns = new ClassUsagePatterns();
        $includePatterns = new IncludeRequirePatterns();
        $classAnalyzer = new ClassAnalyzer($usagePatterns, $includePatterns);
        $fileWriter = new FileWriter();

        $this->graphBuilder = new DependencyGraphBuilder($classAnalyzer, $fileWriter);
        $this->transformer = new ClassNameTransformer(
            suffixTransforms: ['construct' => '', 'Construct' => ''],
            reservedWordFixes: ['Abstract' => 'Abstracts', 'Interface' => 'Interfaces'],
        );
    }

    public function testScanFindsAllFixtureFiles(): void
    {
        $files = $this->fileScanner->scan($this->fixturesPath);
        // 7 class files + 4 consumer files + config.php + helpers.php + index.php = ~11+
        $this->assertGreaterThanOrEqual(10, count($files));
    }

    public function testGraphBuildsFromFixtures(): void
    {
        $files = $this->fileScanner->scan($this->fixturesPath);
        $graph = $this->graphBuilder->build($files);

        $this->assertGreaterThanOrEqual(10, $graph->getTotalFiles());
        // At least 7 class definitions (MyLib_user_construct, MyLib_user_list,
        // MyLib_market_construct, MyLib_market_view, MyLib_UI_Abstract,
        // viewController, Vendor_db_Abstract, Vendor_db_adapter, AdminUserList)
        $this->assertGreaterThanOrEqual(7, $graph->getTotalClasses());
        $this->assertGreaterThan(0, $graph->getTotalIncludes());
    }

    public function testDetectsSpecificClasses(): void
    {
        $files = $this->fileScanner->scan($this->fixturesPath);
        $graph = $this->graphBuilder->build($files);

        $this->assertNotNull($graph->getClassDefinition('MyLib_user_construct'), 'Should find MyLib_user_construct');
        $this->assertNotNull($graph->getClassDefinition('MyLib_user_list'), 'Should find MyLib_user_list');
        $this->assertNotNull($graph->getClassDefinition('MyLib_market_construct'), 'Should find MyLib_market_construct');
        $this->assertNotNull($graph->getClassDefinition('Vendor_db_adapter'), 'Should find Vendor_db_adapter');
        $this->assertNotNull($graph->getClassDefinition('Vendor_db_Abstract'), 'Should find Vendor_db_Abstract');
    }

    public function testNonClassFilesAreNotClasses(): void
    {
        $files = $this->fileScanner->scan($this->fixturesPath);
        $graph = $this->graphBuilder->build($files);

        // helpers.php and config.php should not have class definitions
        foreach ($graph->getAllFileAnalyses() as $analysis) {
            if (str_contains($analysis->filePath, 'helpers.php') || str_contains($analysis->filePath, 'config.php')) {
                $this->assertFalse($analysis->isClassFile(), "{$analysis->relativePath} should not be a class file");
            }
        }
    }

    public function testDetectsDuplicateViewControllerName(): void
    {
        $files = $this->fileScanner->scan($this->fixturesPath);
        $graph = $this->graphBuilder->build($files);

        $dupes = $graph->getDuplicateNames();
        // viewController exists in both UI/ and admin/
        $this->assertNotEmpty($dupes, 'Should detect duplicate class names');
    }

    public function testTransformGeneratesCorrectNamespaces(): void
    {
        $rule = $this->transformer->transform('MyLib_user_construct');
        $this->assertSame('MyLib', $rule->newNamespace);
        // construct suffix dropped -> class name comes from 'user'
        $this->assertSame('User', $rule->newClassName);

        $rule2 = $this->transformer->transform('Vendor_db_adapter');
        $this->assertSame('Vendor\\Db', $rule2->newNamespace);
        $this->assertSame('Adapter', $rule2->newClassName);
    }

    public function testDashboardPageHasIncludes(): void
    {
        $files = $this->fileScanner->scan($this->fixturesPath);
        $graph = $this->graphBuilder->build($files);

        $dashboardAnalysis = null;
        foreach ($graph->getAllFileAnalyses() as $analysis) {
            if (str_contains($analysis->filePath, 'dashboard.php')) {
                $dashboardAnalysis = $analysis;
                break;
            }
        }

        $this->assertNotNull($dashboardAnalysis, 'dashboard.php should be found');
        $this->assertTrue($dashboardAnalysis->hasIncludes(), 'dashboard.php should have includes');
        $this->assertGreaterThanOrEqual(5, count($dashboardAnalysis->includeStatements));
    }

    public function testDashboardPageUsesClasses(): void
    {
        $files = $this->fileScanner->scan($this->fixturesPath);
        $graph = $this->graphBuilder->build($files);

        $dashboardAnalysis = null;
        foreach ($graph->getAllFileAnalyses() as $a) {
            if (str_contains($a->filePath, 'dashboard.php')) {
                $dashboardAnalysis = $a;
                break;
            }
        }

        $this->assertNotNull($dashboardAnalysis);
        $this->assertContains('MyLib_user_construct', $dashboardAnalysis->usedClasses);
        $this->assertContains('MyLib_user_list', $dashboardAnalysis->usedClasses);
        $this->assertContains('MyLib_market_construct', $dashboardAnalysis->usedClasses);
    }

    public function testReservedWordHandling(): void
    {
        $rule = $this->transformer->transform('MyLib_UI_Abstract');
        // 'Abstract' should be fixed to 'Abstracts' somewhere in the FQCN
        $this->assertStringContainsString('Abstracts', $rule->newFullyQualifiedName);
    }
}
