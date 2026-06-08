<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Tests\Domain\Conversion;

use SmartAutoloadConverter\Domain\Conversion\Service\ReservedWordFixer;
use PHPUnit\Framework\TestCase;

class ReservedWordFixerTest extends TestCase
{
    private ReservedWordFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = new ReservedWordFixer();
    }

    public function testFixesAbstract(): void
    {
        $this->assertSame('Abstracts', $this->fixer->fix('Abstract'));
    }

    public function testFixesInterface(): void
    {
        $this->assertSame('Interfaces', $this->fixer->fix('Interface'));
    }

    public function testFixesTrait(): void
    {
        $this->assertSame('Traits', $this->fixer->fix('Trait'));
    }

    public function testFixesList(): void
    {
        $this->assertSame('Lists', $this->fixer->fix('List'));
    }

    public function testDoesNotFixRegularWord(): void
    {
        $this->assertSame('User', $this->fixer->fix('User'));
        $this->assertSame('Market', $this->fixer->fix('Market'));
    }

    public function testFixAllSegments(): void
    {
        $result = $this->fixer->fixAll(['MyLib', 'Abstract', 'User']);
        $this->assertSame(['MyLib', 'Abstracts', 'User'], $result);
    }

    public function testFixNamespaceString(): void
    {
        $result = $this->fixer->fixNamespaceString('MyLib\\Abstract\\Interface');
        $this->assertSame('MyLib\\Abstracts\\Interfaces', $result);
    }

    public function testIsReserved(): void
    {
        $this->assertTrue($this->fixer->isReserved('Abstract'));
        $this->assertFalse($this->fixer->isReserved('Controller'));
    }

    public function testCustomMap(): void
    {
        $fixer = new ReservedWordFixer(['Custom' => 'CustomItems']);
        $this->assertSame('CustomItems', $fixer->fix('Custom'));
        // Default still works
        $this->assertSame('Abstracts', $fixer->fix('Abstract'));
    }
}
