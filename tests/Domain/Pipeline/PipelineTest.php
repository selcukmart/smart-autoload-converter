<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Tests\Domain\Pipeline;

use SmartAutoloadConverter\Domain\Pipeline\Contract\PipelineStepInterface;
use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineContext;
use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineStepStatus;
use SmartAutoloadConverter\Domain\Pipeline\Model\StepResult;
use SmartAutoloadConverter\Domain\Pipeline\Service\Pipeline;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class PipelineTest extends TestCase
{
    private Pipeline $pipeline;
    private PipelineContext $context;

    protected function setUp(): void
    {
        $this->pipeline = new Pipeline(new NullLogger());
        $this->context = new PipelineContext(
            sourcePath: '/tmp/test-source',
            outputPath: '/tmp/test-output',
            config: [],
        );
    }

    public function testExecutesStepsInPriorityOrder(): void
    {
        $order = [];

        $step1 = $this->createStep('second', 200, function() use (&$order) {
            $order[] = 'second';
            return new StepResult('second', PipelineStepStatus::Completed);
        });

        $step2 = $this->createStep('first', 100, function() use (&$order) {
            $order[] = 'first';
            return new StepResult('first', PipelineStepStatus::Completed);
        });

        $this->pipeline->addStep($step1)->addStep($step2);
        $results = $this->pipeline->execute($this->context);

        $this->assertSame(['first', 'second'], $order);
        $this->assertCount(2, $results);
    }

    public function testSkipsStepsNotInOnlyList(): void
    {
        $step1 = $this->createStep('analyze', 100);
        $step2 = $this->createStep('backup', 200);
        $step3 = $this->createStep('rename', 300);

        $this->pipeline->addStep($step1)->addStep($step2)->addStep($step3);
        $results = $this->pipeline->execute($this->context, ['analyze', 'rename']);

        $statuses = array_map(fn(StepResult $r) => [$r->stepName, $r->status], $results);
        $this->assertSame(PipelineStepStatus::Completed, $results[0]->status);
        $this->assertSame(PipelineStepStatus::Skipped, $results[1]->status);
        $this->assertSame(PipelineStepStatus::Completed, $results[2]->status);
    }

    public function testStopsOnErrorWhenConfigured(): void
    {
        $context = new PipelineContext('/tmp/s', '/tmp/o', [], stopOnError: true);

        $step1 = $this->createStep('failing', 100, function() {
            return new StepResult('failing', PipelineStepStatus::Failed, errorMessage: 'broke');
        });
        $step2 = $this->createStep('never_runs', 200);

        $this->pipeline->addStep($step1)->addStep($step2);
        $results = $this->pipeline->execute($context);

        $this->assertCount(1, $results);
        $this->assertSame(PipelineStepStatus::Failed, $results[0]->status);
    }

    public function testContinuesOnErrorWhenConfigured(): void
    {
        $context = new PipelineContext('/tmp/s', '/tmp/o', [], stopOnError: false);

        $step1 = $this->createStep('failing', 100, function() {
            return new StepResult('failing', PipelineStepStatus::Failed, errorMessage: 'broke');
        });
        $step2 = $this->createStep('still_runs', 200);

        $this->pipeline->addStep($step1)->addStep($step2);
        $results = $this->pipeline->execute($context);

        $this->assertCount(2, $results);
    }

    public function testCatchesExceptions(): void
    {
        $step = $this->createStep('throwing', 100, function() {
            throw new \RuntimeException('boom');
        });

        $this->pipeline->addStep($step);
        $results = $this->pipeline->execute($this->context);

        $this->assertCount(1, $results);
        $this->assertSame(PipelineStepStatus::Failed, $results[0]->status);
        $this->assertSame('boom', $results[0]->errorMessage);
    }

    private function createStep(string $name, int $priority, ?\Closure $executor = null): PipelineStepInterface
    {
        $executor ??= fn() => new StepResult($name, PipelineStepStatus::Completed);

        return new class($name, $priority, $executor) implements PipelineStepInterface {
            public function __construct(
                private readonly string $name,
                private readonly int $priority,
                private readonly \Closure $executor,
            ) {}

            public function getName(): string { return $this->name; }
            public function getPriority(): int { return $this->priority; }
            public function supports(PipelineContext $context): bool { return true; }

            public function execute(PipelineContext $context): StepResult
            {
                return ($this->executor)();
            }
        };
    }
}
