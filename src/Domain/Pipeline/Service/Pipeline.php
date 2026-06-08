<?php

declare(strict_types=1);

namespace App\Domain\Pipeline\Service;

use App\Domain\Pipeline\Contract\PipelineStepInterface;
use App\Domain\Pipeline\Model\PipelineContext;
use App\Domain\Pipeline\Model\PipelineStepStatus;
use App\Domain\Pipeline\Model\StepResult;
use Psr\Log\LoggerInterface;

class Pipeline
{
    /** @var PipelineStepInterface[] */
    private array $steps = [];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function addStep(PipelineStepInterface $step): self
    {
        $this->steps[] = $step;
        return $this;
    }

    /**
     * @param string[]|null $onlySteps Run only these steps (null = all)
     * @return StepResult[]
     */
    public function execute(PipelineContext $context, ?array $onlySteps = null): array
    {
        usort($this->steps, fn(PipelineStepInterface $a, PipelineStepInterface $b) => $a->getPriority() <=> $b->getPriority());

        $results = [];

        foreach ($this->steps as $step) {
            if ($onlySteps !== null && !in_array($step->getName(), $onlySteps, true)) {
                $results[] = new StepResult(
                    stepName: $step->getName(),
                    status: PipelineStepStatus::Skipped,
                );
                continue;
            }

            if (!$step->supports($context)) {
                $this->logger->info("Step {$step->getName()} skipped (not supported for this config)");
                $results[] = new StepResult(
                    stepName: $step->getName(),
                    status: PipelineStepStatus::Skipped,
                );
                continue;
            }

            $label = $step->getName() . ($context->dryRun ? ' (dry-run)' : '');
            $this->logger->info("Starting step: {$label}");

            $startTime = microtime(true);

            try {
                $result = $step->execute($context);
                $results[] = $result;

                $this->logger->info("Step {$step->getName()}: {$result->status->value} ({$result->filesChanged} files)");

                if (!$result->isSuccessful() && $context->stopOnError) {
                    $this->logger->error("Pipeline stopped at step: {$step->getName()}");
                    break;
                }
            } catch (\Throwable $e) {
                $results[] = new StepResult(
                    stepName: $step->getName(),
                    status: PipelineStepStatus::Failed,
                    errorMessage: $e->getMessage(),
                    durationSeconds: microtime(true) - $startTime,
                );

                $this->logger->error("Step {$step->getName()} failed: {$e->getMessage()}");

                if ($context->stopOnError) {
                    break;
                }
            }
        }

        return $results;
    }
}
