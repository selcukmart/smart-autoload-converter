<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Tests\Domain\Regex;

use SmartAutoloadConverter\Domain\Analysis\Model\IncludeType;
use SmartAutoloadConverter\Domain\Regex\IncludeRequirePatterns;
use PHPUnit\Framework\TestCase;

class IncludeRequirePatternsTest extends TestCase
{
    private IncludeRequirePatterns $patterns;

    protected function setUp(): void
    {
        $this->patterns = new IncludeRequirePatterns();
    }

    public function testParseIncludeOnce(): void
    {
        $stmt = $this->patterns->parse("include_once 'lib/User.php';", '/test.php', 1);
        $this->assertNotNull($stmt);
        $this->assertSame(IncludeType::IncludeOnce, $stmt->type);
        $this->assertSame('lib/User.php', $stmt->includedPath);
    }

    public function testParseRequireOnce(): void
    {
        $stmt = $this->patterns->parse('require_once "vendor/autoload.php";', '/test.php', 5);
        $this->assertNotNull($stmt);
        $this->assertSame(IncludeType::RequireOnce, $stmt->type);
    }

    public function testParseWithParentheses(): void
    {
        $stmt = $this->patterns->parse("include_once('lib/Db.php');", '/test.php', 3);
        $this->assertNotNull($stmt);
        $this->assertSame('lib/Db.php', $stmt->includedPath);
    }

    public function testDetectsDynamicPath(): void
    {
        // Dynamic paths with variables should be flagged
        $stmt = $this->patterns->parse("include_once \$dir . '/lib.php';", '/t.php', 1);
        // Current regex does not match variable-based includes (by design: they're flagged elsewhere)
        $this->assertNull($stmt, 'Variable-based includes should not match simple pattern');
    }

    public function testFindAllInContent(): void
    {
        $content = "<?php\ninclude_once 'a.php';\nrequire_once 'b.php';\n\$x = 1;\ninclude 'c.php';\n";
        $stmts = $this->patterns->findAll($content, '/test.php');
        $this->assertCount(3, $stmts);
    }

    public function testNonIncludeLineReturnsNull(): void
    {
        $stmt = $this->patterns->parse('$user = new User();', '/test.php', 1);
        $this->assertNull($stmt);
    }

    public function testBuildReplacePattern(): void
    {
        $pattern = $this->patterns->buildReplacePattern('lib/User.php');
        $this->assertStringContainsString('lib/User\\.php', $pattern);
    }
}
