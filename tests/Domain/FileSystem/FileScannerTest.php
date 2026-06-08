<?php

declare(strict_types=1);

namespace App\Tests\Domain\FileSystem;

use App\Domain\FileSystem\Service\FileScanner;
use PHPUnit\Framework\TestCase;

class FileScannerTest extends TestCase
{
    private FileScanner $scanner;
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->scanner = new FileScanner();
        $this->fixturesPath = dirname(__DIR__, 3) . '/fixtures/legacy-sample-project';
    }

    public function testScansAllPhpFiles(): void
    {
        $files = $this->scanner->scan($this->fixturesPath);
        // Should find all .php files in the fixture project
        $this->assertNotEmpty($files);
        $this->assertGreaterThanOrEqual(10, count($files));
    }

    public function testRespectsIgnorePatterns(): void
    {
        $all = $this->scanner->scan($this->fixturesPath);
        $filtered = $this->scanner->scan($this->fixturesPath, ['Vendor/']);

        $this->assertLessThan(count($all), count($filtered));
    }

    public function testRespectsIgnoreFiles(): void
    {
        $all = $this->scanner->scan($this->fixturesPath);
        $filtered = $this->scanner->scan($this->fixturesPath, [], ['config.php']);

        $this->assertLessThan(count($all), count($filtered));
    }

    public function testReturnsScannedFileObjects(): void
    {
        $files = $this->scanner->scan($this->fixturesPath);
        $first = $files[0];

        $this->assertNotEmpty($first->path);
        $this->assertNotEmpty($first->relativePath);
        $this->assertGreaterThan(0, $first->size);
        $this->assertNotEmpty($first->hash);
    }

    public function testSortsByName(): void
    {
        $files = $this->scanner->scan($this->fixturesPath);
        $paths = array_map(fn($f) => $f->relativePath, $files);
        $sorted = $paths;
        sort($sorted);
        $this->assertSame($sorted, $paths);
    }
}
