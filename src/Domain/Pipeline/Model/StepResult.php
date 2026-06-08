<?php

declare(strict_types=1);

namespace App\Domain\Pipeline\Model;

class StepResult
{
    /**
     * @param array<string, mixed> $changes
     */
    public function __construct(
        public string $stepName,
        public PipelineStepStatus $status,
        public int $filesChanged = 0,
        public array $changes = [],
        public ?string $errorMessage = null,
        public float $durationSeconds = 0.0,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status === PipelineStepStatus::Completed;
    }
}
