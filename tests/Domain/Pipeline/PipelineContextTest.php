<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Tests\Domain\Pipeline;

use SmartAutoloadConverter\Domain\Analysis\Model\DependencyGraph;
use SmartAutoloadConverter\Domain\Conversion\Model\ConversionRule;
use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineContext;
use PHPUnit\Framework\TestCase;

class PipelineContextTest extends TestCase
{
    public function testBasicProperties(): void
    {
        $ctx = new PipelineContext('/src', '/out', ['key' => 'val'], dryRun: true, stopOnError: false);

        $this->assertSame('/src', $ctx->sourcePath);
        $this->assertSame('/out', $ctx->outputPath);
        $this->assertTrue($ctx->dryRun);
        $this->assertFalse($ctx->stopOnError);
    }

    public function testConfigValueDotNotation(): void
    {
        $ctx = new PipelineContext('/s', '/o', ['backup' => ['enabled' => true, 'strategy' => 'zip']]);

        $this->assertTrue($ctx->getConfigValue('backup.enabled'));
        $this->assertSame('zip', $ctx->getConfigValue('backup.strategy'));
        $this->assertNull($ctx->getConfigValue('nonexistent'));
        $this->assertSame('default', $ctx->getConfigValue('missing.key', 'default'));
    }

    public function testDependencyGraphStorage(): void
    {
        $ctx = new PipelineContext('/s', '/o', []);
        $this->assertNull($ctx->getDependencyGraph());

        $graph = new DependencyGraph();
        $ctx->setDependencyGraph($graph);
        $this->assertSame($graph, $ctx->getDependencyGraph());
    }

    public function testConversionRules(): void
    {
        $ctx = new PipelineContext('/s', '/o', []);
        $this->assertEmpty($ctx->getConversionRules());

        $rule = new ConversionRule('Old', 'New', 'NS', 'NS\\New', '/old.php', '/new.php');
        $ctx->addConversionRule($rule);

        $this->assertCount(1, $ctx->getConversionRules());
        $this->assertSame($rule, $ctx->getConversionRules()['Old']);
    }

    public function testFileContentCache(): void
    {
        $ctx = new PipelineContext('/s', '/o', []);
        $this->assertNull($ctx->getFileContent('/test.php'));

        $ctx->setFileContent('/test.php', '<?php echo "hi";');
        $this->assertSame('<?php echo "hi";', $ctx->getFileContent('/test.php'));
    }

    public function testManualReview(): void
    {
        $ctx = new PipelineContext('/s', '/o', []);
        $ctx->addManualReview('/file.php', 'Dynamic include');

        $reviews = $ctx->getManualReviewFiles();
        $this->assertArrayHasKey('/file.php', $reviews);
        $this->assertSame('Dynamic include', $reviews['/file.php']);
    }

    public function testStepData(): void
    {
        $ctx = new PipelineContext('/s', '/o', []);
        $this->assertNull($ctx->getStepData('backup'));

        $ctx->setStepData('backup', ['path' => '/backup.zip']);
        $this->assertSame(['path' => '/backup.zip'], $ctx->getStepData('backup'));
    }
}
