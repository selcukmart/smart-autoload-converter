<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Tests\Domain\Analysis;

use SmartAutoloadConverter\Domain\Analysis\Model\ClassType;
use SmartAutoloadConverter\Domain\Analysis\Service\ClassAnalyzer;
use SmartAutoloadConverter\Domain\Regex\ClassUsagePatterns;
use SmartAutoloadConverter\Domain\Regex\IncludeRequirePatterns;
use PHPUnit\Framework\TestCase;

class ClassAnalyzerTest extends TestCase
{
    private ClassAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new ClassAnalyzer(
            new ClassUsagePatterns(),
            new IncludeRequirePatterns(),
        );
    }

    public function testDetectsClassDefinition(): void
    {
        $content = "<?php\nclass MyLib_user_construct\n{\n}\n";
        $result = $this->analyzer->analyze('/test.php', 'test.php', $content);

        $this->assertTrue($result->isClassFile());
        $this->assertCount(1, $result->classDefinitions);
        $this->assertSame('MyLib_user_construct', $result->classDefinitions[0]->originalName);
    }

    public function testDetectsAbstractClass(): void
    {
        $content = "<?php\nabstract class MyLib_UI_Abstract\n{\n}\n";
        $result = $this->analyzer->analyze('/test.php', 'test.php', $content);

        $this->assertTrue($result->isClassFile());
        $this->assertSame(ClassType::AbstractClass, $result->classDefinitions[0]->type);
    }

    public function testDetectsInterface(): void
    {
        $content = "<?php\ninterface MyInterface\n{\n}\n";
        $result = $this->analyzer->analyze('/test.php', 'test.php', $content);

        $this->assertTrue($result->isClassFile());
        $this->assertSame(ClassType::Interface_, $result->classDefinitions[0]->type);
    }

    public function testDetectsTrait(): void
    {
        $content = "<?php\ntrait MyTrait\n{\n}\n";
        $result = $this->analyzer->analyze('/test.php', 'test.php', $content);

        $this->assertTrue($result->isClassFile());
        $this->assertSame(ClassType::Trait_, $result->classDefinitions[0]->type);
    }

    public function testDetectsNamespace(): void
    {
        $content = "<?php\nnamespace MyApp\\Models;\nclass User {}\n";
        $result = $this->analyzer->analyze('/test.php', 'test.php', $content);

        $this->assertTrue($result->hasNamespace);
        $this->assertSame('MyApp\\Models', $result->existingNamespace);
    }

    public function testDetectsExtendsRelation(): void
    {
        $content = "<?php\nclass ChildClass extends ParentClass {\n}\n";
        $result = $this->analyzer->analyze('/test.php', 'test.php', $content);

        $this->assertSame('ParentClass', $result->classDefinitions[0]->extends);
    }

    public function testDetectsImplements(): void
    {
        $content = "<?php\nclass MyClass implements Serializable, Countable {\n}\n";
        $result = $this->analyzer->analyze('/test.php', 'test.php', $content);

        $this->assertContains('Serializable', $result->classDefinitions[0]->implements);
        $this->assertContains('Countable', $result->classDefinitions[0]->implements);
    }

    public function testDetectsIncludeStatements(): void
    {
        $content = "<?php\ninclude_once 'lib/User.php';\nrequire_once 'lib/Db.php';\n\$x = 1;\n";
        $result = $this->analyzer->analyze('/test.php', 'test.php', $content);

        $this->assertTrue($result->hasIncludes());
        $this->assertCount(2, $result->includeStatements);
    }

    public function testDetectsClassUsages(): void
    {
        $content = "<?php\n\$u = new MyUser();\nif (\$u instanceof MyUser) {}\nMyUser::create();\n";
        $result = $this->analyzer->analyze('/test.php', 'test.php', $content);

        $this->assertContains('MyUser', $result->usedClasses);
    }

    public function testNonClassFile(): void
    {
        $content = "<?php\nfunction helper() { return 1; }\n";
        $result = $this->analyzer->analyze('/test.php', 'test.php', $content);

        $this->assertFalse($result->isClassFile());
        $this->assertEmpty($result->classDefinitions);
    }
}
