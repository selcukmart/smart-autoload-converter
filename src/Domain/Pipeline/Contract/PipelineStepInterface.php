<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Pipeline\Contract;

use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineContext;
use SmartAutoloadConverter\Domain\Pipeline\Model\StepResult;

interface PipelineStepInterface
{
    public function getName(): string;

    public function execute(PipelineContext $context): StepResult;

    public function supports(PipelineContext $context): bool;

    public function getPriority(): int;
}
