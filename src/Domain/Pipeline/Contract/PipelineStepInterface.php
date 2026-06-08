<?php

declare(strict_types=1);

namespace App\Domain\Pipeline\Contract;

use App\Domain\Pipeline\Model\PipelineContext;
use App\Domain\Pipeline\Model\StepResult;

interface PipelineStepInterface
{
    public function getName(): string;

    public function execute(PipelineContext $context): StepResult;

    public function supports(PipelineContext $context): bool;

    public function getPriority(): int;
}
