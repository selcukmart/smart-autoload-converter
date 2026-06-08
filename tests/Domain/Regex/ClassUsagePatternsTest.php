<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Tests\Domain\Regex;

use SmartAutoloadConverter\Domain\Regex\ClassUsagePatterns;
use PHPUnit\Framework\TestCase;

class ClassUsagePatternsTest extends TestCase
{
    private ClassUsagePatterns $patterns;

    protected function setUp(): void
    {
        $this->patterns = new ClassUsagePatterns();
    }

    public function testFindsNewInstances(): void
    {
        $code = '$user = new MyLib_user_construct();';
        $classes = $this->patterns->findClassUsages($code);
        $this->assertContains('MyLib_user_construct', $classes);
    }

    public function testFindsStaticCalls(): void
    {
        $code = 'MyLib_user_construct::create();';
        $classes = $this->patterns->findClassUsages($code);
        $this->assertContains('MyLib_user_construct', $classes);
    }

    public function testFindsInstanceof(): void
    {
        $code = 'if ($user instanceof MyLib_user_construct) {}';
        $classes = $this->patterns->findClassUsages($code);
        $this->assertContains('MyLib_user_construct', $classes);
    }

    public function testFindsReturnType(): void
    {
        $code = 'function getUser():MyLib_user_construct { }';
        $classes = $this->patterns->findClassUsages($code);
        $this->assertContains('MyLib_user_construct', $classes);
    }

    public function testFiltersPHPBuiltins(): void
    {
        $code = 'function test(string $a, int $b, MyClass $c): void {}';
        $classes = $this->patterns->findClassUsages($code);
        $this->assertNotContains('string', $classes);
        $this->assertNotContains('int', $classes);
        $this->assertNotContains('void', $classes);
    }

    public function testFindsFunctionParams(): void
    {
        $code = 'function showUser(MyLib_user_construct $user) {}';
        $classes = $this->patterns->findClassUsages($code);
        $this->assertContains('MyLib_user_construct', $classes);
    }

    public function testBuildReplacePattern(): void
    {
        $pattern = $this->patterns->buildReplacePattern(ClassUsagePatterns::REPLACE_NEW, 'MyClass');
        $this->assertStringContainsString('MyClass', $pattern);
    }
}
