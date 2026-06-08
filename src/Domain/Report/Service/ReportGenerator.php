<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Report\Service;

use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineContext;
use SmartAutoloadConverter\Domain\Pipeline\Model\StepResult;
use SmartAutoloadConverter\Domain\Report\Model\ConversionReport;

/**
 * Generates a ConversionReport from pipeline execution results.
 */
class ReportGenerator
{
    /**
     * @param StepResult[] $stepResults
     */
    public function generate(PipelineContext $context, array $stepResults): ConversionReport
    {
        $totalDuration = 0.0;
        foreach ($stepResults as $result) {
            $totalDuration += $result->durationSeconds;
        }

        return new ConversionReport(
            sourcePath: $context->sourcePath,
            outputPath: $context->outputPath,
            dryRun: $context->dryRun,
            stepResults: $stepResults,
            manualReviewFiles: $context->getManualReviewFiles(),
            totalDurationSeconds: $totalDuration,
        );
    }
}
