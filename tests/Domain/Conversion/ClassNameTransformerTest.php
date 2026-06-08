<?php

declare(strict_types=1);

namespace App\Tests\Domain\Conversion;

use App\Domain\Conversion\Service\ClassNameTransformer;
use PHPUnit\Framework\TestCase;

class ClassNameTransformerTest extends TestCase
{
    private ClassNameTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new ClassNameTransformer(
            suffixTransforms: ['construct' => '', 'Construct' => '', 'index' => ''],
            reservedWordFixes: ['Abstract' => 'Abstracts', 'Interface' => 'Interfaces'],
            separator: '_',
        );
    }

    public function testSimpleUnderscoreClass(): void
    {
        $rule = $this->transformer->transform('MyLib_user_construct');
        $this->assertSame('MyLib_user_construct', $rule->oldClassName);
        // 'construct' suffix is dropped
        $this->assertSame('User', $rule->newClassName);
        $this->assertSame('MyLib', $rule->newNamespace);
        $this->assertSame('MyLib\\User', $rule->newFullyQualifiedName);
    }

    public function testThreePartClass(): void
    {
        $rule = $this->transformer->transform('MyLib_market_view');
        $this->assertSame('View', $rule->newClassName);
        $this->assertSame('MyLib\\Market', $rule->newNamespace);
        $this->assertSame('MyLib\\Market\\View', $rule->newFullyQualifiedName);
    }

    public function testReservedWordInNamespace(): void
    {
        $rule = $this->transformer->transform('MyLib_UI_Abstract');
        // 'Abstract' -> 'Abstracts' in namespace
        $this->assertStringContainsString('Abstracts', $rule->newFullyQualifiedName);
    }

    public function testSingleWordClass(): void
    {
        $rule = $this->transformer->transform('SomeHelper');
        $this->assertSame('SomeHelper', $rule->newClassName);
        $this->assertSame('', $rule->newNamespace);
        $this->assertSame('SomeHelper', $rule->newFullyQualifiedName);
    }

    public function testVendorClass(): void
    {
        $rule = $this->transformer->transform('Vendor_db_adapter');
        $this->assertSame('Adapter', $rule->newClassName);
        $this->assertSame('Vendor\\Db', $rule->newNamespace);
        $this->assertSame('Vendor\\Db\\Adapter', $rule->newFullyQualifiedName);
    }

    public function testConstructSuffixDropped(): void
    {
        $rule = $this->transformer->transform('MyLib_user_construct');
        // construct suffix maps to '' so it's dropped, class becomes last remaining part
        $this->assertNotEquals('Construct', $rule->newClassName);
    }

    public function testCustomSeparator(): void
    {
        $transformer = new ClassNameTransformer(separator: '-');
        $rule = $transformer->transform('MyLib-user-list');
        $this->assertSame('List', $rule->newClassName);
        $this->assertSame('MyLib\\User', $rule->newNamespace);
    }
}
