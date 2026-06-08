<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Tests\Domain\Analysis;

use SmartAutoloadConverter\Domain\Analysis\Model\ClassDefinition;
use SmartAutoloadConverter\Domain\Analysis\Model\ClassType;
use SmartAutoloadConverter\Domain\Analysis\Model\DependencyGraph;
use SmartAutoloadConverter\Domain\Analysis\Model\FileAnalysis;
use SmartAutoloadConverter\Domain\Analysis\Model\IncludeStatement;
use SmartAutoloadConverter\Domain\Analysis\Model\IncludeType;
use PHPUnit\Framework\TestCase;

class DependencyGraphTest extends TestCase
{
    private DependencyGraph $graph;

    protected function setUp(): void
    {
        $this->graph = new DependencyGraph();
    }

    public function testAddAndRetrieveFileAnalysis(): void
    {
        $classDef = new ClassDefinition('MyClass', '/src/MyClass.php', ClassType::Class_, className: 'MyClass');
        $analysis = new FileAnalysis('/src/MyClass.php', 'src/MyClass.php', [$classDef]);

        $this->graph->addFileAnalysis($analysis);

        $this->assertSame($analysis, $this->graph->getFileAnalysis('/src/MyClass.php'));
        $this->assertSame(1, $this->graph->getTotalFiles());
    }

    public function testTracksClassDefinitions(): void
    {
        $classDef = new ClassDefinition('UserModel', '/User.php', ClassType::Class_, className: 'UserModel');
        $analysis = new FileAnalysis('/User.php', 'User.php', [$classDef]);

        $this->graph->addFileAnalysis($analysis);

        $this->assertNotNull($this->graph->getClassDefinition('UserModel'));
        $this->assertSame(1, $this->graph->getTotalClasses());
    }

    public function testTracksClassUsages(): void
    {
        $analysis = new FileAnalysis('/page.php', 'page.php', usedClasses: ['UserModel', 'DbAdapter']);
        $this->graph->addFileAnalysis($analysis);

        $usages = $this->graph->getClassUsages('UserModel');
        $this->assertContains('/page.php', $usages);
    }

    public function testDetectsUnusedClasses(): void
    {
        $classDef = new ClassDefinition('UnusedClass', '/Unused.php', ClassType::Class_, className: 'UnusedClass');
        $this->graph->addFileAnalysis(new FileAnalysis('/Unused.php', 'Unused.php', [$classDef]));
        // No file uses UnusedClass
        $this->graph->addFileAnalysis(new FileAnalysis('/page.php', 'page.php', usedClasses: ['OtherClass']));

        $unused = $this->graph->getUnusedClasses();
        $this->assertContains('UnusedClass', $unused);
    }

    public function testGetClassFiles(): void
    {
        $classDef = new ClassDefinition('A', '/A.php', ClassType::Class_, className: 'A');
        $this->graph->addFileAnalysis(new FileAnalysis('/A.php', 'A.php', [$classDef]));
        $this->graph->addFileAnalysis(new FileAnalysis('/b.php', 'b.php')); // no class

        $classFiles = $this->graph->getClassFiles();
        $this->assertCount(1, $classFiles);
    }

    public function testGetFilesWithIncludes(): void
    {
        $inc = new IncludeStatement('/a.php', 1, IncludeType::IncludeOnce, 'lib.php');
        $this->graph->addFileAnalysis(new FileAnalysis('/a.php', 'a.php', includeStatements: [$inc]));
        $this->graph->addFileAnalysis(new FileAnalysis('/b.php', 'b.php'));

        $this->assertCount(1, $this->graph->getFilesWithIncludes());
        $this->assertSame(1, $this->graph->getTotalIncludes());
    }

    public function testDuplicateNames(): void
    {
        $this->graph->setDuplicateNames(['viewController' => ['UI_viewController', 'Admin_viewController']]);

        $this->assertTrue($this->graph->hasDuplicateName('viewController'));
        $this->assertFalse($this->graph->hasDuplicateName('UserModel'));
    }
}
