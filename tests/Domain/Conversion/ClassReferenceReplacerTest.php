<?php

declare(strict_types=1);

namespace App\Tests\Domain\Conversion;

use App\Domain\Conversion\Model\ConversionRule;
use App\Domain\Conversion\Service\ClassReferenceReplacer;
use PHPUnit\Framework\TestCase;

class ClassReferenceReplacerTest extends TestCase
{
    private ClassReferenceReplacer $replacer;

    protected function setUp(): void
    {
        $this->replacer = new ClassReferenceReplacer();
    }

    private function makeRule(string $old, string $newClass, string $ns): ConversionRule
    {
        $fqcn = $ns ? "{$ns}\\{$newClass}" : $newClass;
        return new ConversionRule($old, $newClass, $ns, $fqcn, '', '');
    }

    public function testReplacesNewInstance(): void
    {
        $content = '<?php $u = new MyLib_user_construct();';
        $rules = ['MyLib_user_construct' => $this->makeRule('MyLib_user_construct', 'User', 'MyLib')];

        $result = $this->replacer->replace($content, $rules);
        $this->assertStringContainsString('new MyLib\User()', $result->modifiedContent);
        $this->assertTrue($result->hasChanges());
    }

    public function testReplacesStaticCall(): void
    {
        $content = '<?php MyLib_user_construct::create();';
        $rules = ['MyLib_user_construct' => $this->makeRule('MyLib_user_construct', 'User', 'MyLib')];

        $result = $this->replacer->replace($content, $rules);
        $this->assertStringContainsString('MyLib\User::', $result->modifiedContent);
    }

    public function testReplacesExtends(): void
    {
        $content = "<?php\nclass ChildClass extends MyLib_user_construct {\n}\n";
        $rules = ['MyLib_user_construct' => $this->makeRule('MyLib_user_construct', 'User', 'MyLib')];

        $result = $this->replacer->replace($content, $rules);
        $this->assertStringContainsString('extends MyLib\User', $result->modifiedContent);
    }

    public function testReplacesInstanceof(): void
    {
        $content = '<?php if ($u instanceof MyLib_user_construct) {}';
        $rules = ['MyLib_user_construct' => $this->makeRule('MyLib_user_construct', 'User', 'MyLib')];

        $result = $this->replacer->replace($content, $rules);
        $this->assertStringContainsString('instanceof MyLib\User', $result->modifiedContent);
    }

    public function testReplacesCatch(): void
    {
        $content = '<?php try {} catch (MyLib_user_construct $e) {}';
        $rules = ['MyLib_user_construct' => $this->makeRule('MyLib_user_construct', 'User', 'MyLib')];

        $result = $this->replacer->replace($content, $rules);
        $this->assertStringContainsString('catch (MyLib\User ', $result->modifiedContent);
    }

    public function testDoesNotReplaceClassDefinitionItself(): void
    {
        // "hide and seek" protection: class definition should NOT be changed
        $content = "<?php\nclass MyLib_user_construct {\n    \$x = new MyLib_user_construct();\n}\n";
        $rules = ['MyLib_user_construct' => $this->makeRule('MyLib_user_construct', 'User', 'MyLib')];

        $result = $this->replacer->replace($content, $rules);
        // The class definition line should still say "class MyLib_user_construct"
        $this->assertStringContainsString('class MyLib_user_construct', $result->modifiedContent);
        // But the usage inside should be replaced
        $this->assertStringContainsString('new MyLib\User()', $result->modifiedContent);
    }

    public function testNoChangesReturnsZeroCount(): void
    {
        $content = '<?php echo "hello";';
        $rules = ['MyLib_user_construct' => $this->makeRule('MyLib_user_construct', 'User', 'MyLib')];

        $result = $this->replacer->replace($content, $rules);
        $this->assertFalse($result->hasChanges());
        $this->assertSame(0, $result->changeCount);
    }

    public function testAddNamespaceAndUse(): void
    {
        $content = "<?php\n\nclass User {}\n";
        $result = $this->replacer->addNamespaceAndUse($content, 'MyLib\\User', ['MyLib\\Db\\Adapter']);

        $this->assertStringContainsString('namespace MyLib\\User;', $result);
        $this->assertStringContainsString('use MyLib\\Db\\Adapter;', $result);
    }

    public function testAddNamespaceAfterDeclare(): void
    {
        $content = "<?php\ndeclare(strict_types=1);\n\nclass User {}\n";
        $result = $this->replacer->addNamespaceAndUse($content, 'MyLib\\User');

        $this->assertStringContainsString('namespace MyLib\\User;', $result);
        // namespace should come after declare
        $pos_declare = strpos($result, 'declare');
        $pos_namespace = strpos($result, 'namespace');
        $this->assertGreaterThan($pos_declare, $pos_namespace);
    }
}
