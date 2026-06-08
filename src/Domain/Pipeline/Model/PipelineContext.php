<?php

declare(strict_types=1);

namespace App\Domain\Pipeline\Model;

use App\Domain\Analysis\Model\DependencyGraph;
use App\Domain\Conversion\Model\ConversionRule;

/**
 * Mutable context object passed through all pipeline steps.
 * Each step reads what it needs and writes its results here.
 */
class PipelineContext
{
    private ?DependencyGraph $dependencyGraph = null;

    /** @var ConversionRule[] */
    private array $conversionRules = [];

    /** @var array<string, string> Map of filePath => content (modified in memory) */
    private array $fileContents = [];

    /** @var string[] Files flagged for manual review */
    private array $manualReviewFiles = [];

    /** @var array<string, mixed> Arbitrary step data keyed by step name */
    private array $stepData = [];

    public function __construct(
        public readonly string $sourcePath,
        public readonly string $outputPath,
        public readonly array $config,
        public readonly bool $dryRun = false,
        public readonly bool $stopOnError = true,
    ) {}

    // === DependencyGraph ===

    public function getDependencyGraph(): ?DependencyGraph
    {
        return $this->dependencyGraph;
    }

    public function setDependencyGraph(DependencyGraph $graph): void
    {
        $this->dependencyGraph = $graph;
    }

    // === ConversionRules ===

    /** @return ConversionRule[] */
    public function getConversionRules(): array
    {
        return $this->conversionRules;
    }

    /** @param ConversionRule[] $rules */
    public function setConversionRules(array $rules): void
    {
        $this->conversionRules = $rules;
    }

    public function addConversionRule(ConversionRule $rule): void
    {
        $this->conversionRules[$rule->oldClassName] = $rule;
    }

    // === File Contents ===

    public function getFileContent(string $filePath): ?string
    {
        return $this->fileContents[$filePath] ?? null;
    }

    public function setFileContent(string $filePath, string $content): void
    {
        $this->fileContents[$filePath] = $content;
    }

    /** @return array<string, string> */
    public function getAllFileContents(): array
    {
        return $this->fileContents;
    }

    // === Manual Review ===

    public function addManualReview(string $filePath, string $reason): void
    {
        $this->manualReviewFiles[$filePath] = $reason;
    }

    /** @return array<string, string> */
    public function getManualReviewFiles(): array
    {
        return $this->manualReviewFiles;
    }

    // === Step Data (generic key-value per step) ===

    public function setStepData(string $stepName, mixed $data): void
    {
        $this->stepData[$stepName] = $data;
    }

    public function getStepData(string $stepName): mixed
    {
        return $this->stepData[$stepName] ?? null;
    }

    // === Config helpers ===

    public function getConfigValue(string $dotPath, mixed $default = null): mixed
    {
        $keys = explode('.', $dotPath);
        $value = $this->config;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return $default;
            }
            $value = $value[$key];
        }

        return $value;
    }

    // === Path helpers ===

    /**
     * Translate a source path to the equivalent output path.
     * Example: /workspace/input/include/User.php → /workspace/output/include/User.php
     */
    public function toOutputPath(string $sourcePath): string
    {
        if (str_starts_with($sourcePath, $this->sourcePath)) {
            $relative = substr($sourcePath, strlen(rtrim($this->sourcePath, '/')));
            return rtrim($this->outputPath, '/') . $relative;
        }
        return $sourcePath;
    }
}
