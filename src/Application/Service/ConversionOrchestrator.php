<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Pipeline\Model\PipelineContext;
use App\Domain\Pipeline\Model\StepResult;
use App\Domain\Pipeline\Service\Pipeline;
use Psr\Log\LoggerInterface;

/**
 * Main entry point for the conversion process.
 * Loads config, creates PipelineContext, executes the pipeline.
 */
class ConversionOrchestrator
{
    public function __construct(
        private readonly ConfigurationLoader $configLoader,
        private readonly Pipeline $pipeline,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Run the full conversion pipeline.
     *
     * @param string[]|null $steps Specific steps to run (null = all)
     * @return StepResult[]
     */
    public function convert(
        string $targetPath,
        string $exportPath,
        ?string $configPath = null,
        bool $dryRun = false,
        ?array $steps = null,
    ): array {
        // 1. Load and merge configuration
        $config = $this->configLoader->load($configPath);
        $config = $this->configLoader->applyOverrides($config, [
            'target-path' => $targetPath,
            'export-path' => $exportPath,
            'dry-run' => $dryRun,
        ]);

        if ($steps !== null) {
            $config['pipeline']['steps'] = $steps;
        }

        // 2. Build pipeline context
        $context = new PipelineContext(
            sourcePath: $config['source']['path'],
            outputPath: $config['output']['path'],
            config: $config,
            dryRun: $dryRun,
            stopOnError: $config['pipeline']['stop_on_error'] ?? true,
        );

        // 3. Execute pipeline
        $this->logger->info('Starting conversion pipeline', [
            'source' => $context->sourcePath,
            'output' => $context->outputPath,
            'dry_run' => $dryRun,
            'steps' => $steps,
        ]);

        $results = $this->pipeline->execute($context, $steps);

        // 4. Summary
        $completed = count(array_filter($results, fn(StepResult $r) => $r->isSuccessful()));
        $failed = count(array_filter($results, fn(StepResult $r) => $r->status->value === 'failed'));
        $this->logger->info("Pipeline finished: {$completed} completed, {$failed} failed");

        return $results;
    }
}
