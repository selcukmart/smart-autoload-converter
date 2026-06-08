<?php

declare(strict_types=1);

namespace App\Domain\Report\Model;

use App\Domain\Pipeline\Model\StepResult;

/**
 * Full conversion report with statistics and step details.
 */
readonly class ConversionReport
{
    /**
     * @param StepResult[] $stepResults
     * @param array<string, string> $manualReviewFiles
     */
    public function __construct(
        public string $sourcePath,
        public string $outputPath,
        public bool $dryRun,
        public array $stepResults,
        public array $manualReviewFiles = [],
        public float $totalDurationSeconds = 0.0,
        public \DateTimeImmutable $generatedAt = new \DateTimeImmutable(),
    ) {}

    public function getTotalFilesChanged(): int
    {
        $count = 0;
        foreach ($this->stepResults as $result) {
            $count += $result->filesChanged;
        }
        return $count;
    }

    public function getCompletedSteps(): int
    {
        return count(array_filter($this->stepResults, fn(StepResult $r) => $r->isSuccessful()));
    }

    public function getFailedSteps(): int
    {
        return count(array_filter($this->stepResults, fn(StepResult $r) => $r->status->value === 'failed'));
    }

    public function isSuccessful(): bool
    {
        return $this->getFailedSteps() === 0;
    }

    public function toArray(): array
    {
        return [
            'source_path' => $this->sourcePath,
            'output_path' => $this->outputPath,
            'dry_run' => $this->dryRun,
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
            'total_duration_seconds' => round($this->totalDurationSeconds, 3),
            'total_files_changed' => $this->getTotalFilesChanged(),
            'completed_steps' => $this->getCompletedSteps(),
            'failed_steps' => $this->getFailedSteps(),
            'manual_review_files' => $this->manualReviewFiles,
            'steps' => array_map(fn(StepResult $r) => [
                'name' => $r->stepName,
                'status' => $r->status->value,
                'files_changed' => $r->filesChanged,
                'duration_seconds' => round($r->durationSeconds, 3),
                'error' => $r->errorMessage,
                'changes' => $r->changes,
            ], $this->stepResults),
        ];
    }
}
