<?php

declare(strict_types=1);

namespace App\Domain\Report\Exporter;

use App\Domain\Report\Model\ConversionReport;

class ConsoleExporter implements ReportExporterInterface
{
    public function getFormat(): string
    {
        return 'console';
    }

    public function export(ConversionReport $report): string
    {
        $lines = [];
        $lines[] = str_repeat('=', 60);
        $lines[] = '  SMART AUTOLOAD CONVERTER REPORT';
        $lines[] = str_repeat('=', 60);
        $lines[] = '';

        $status = $report->isSuccessful() ? 'SUCCESS' : 'FAILED';
        $mode = $report->dryRun ? ' (DRY RUN)' : '';
        $lines[] = "Status:         {$status}{$mode}";
        $lines[] = "Source:         {$report->sourcePath}";
        $lines[] = "Output:         {$report->outputPath}";
        $lines[] = "Duration:       " . round($report->totalDurationSeconds, 2) . "s";
        $lines[] = "Files Changed:  {$report->getTotalFilesChanged()}";
        $lines[] = "Steps OK:       {$report->getCompletedSteps()}";
        $lines[] = "Steps Failed:   {$report->getFailedSteps()}";
        $lines[] = '';
        $lines[] = str_repeat('-', 60);
        $lines[] = sprintf('  %-20s %-12s %6s %8s', 'STEP', 'STATUS', 'FILES', 'TIME');
        $lines[] = str_repeat('-', 60);

        foreach ($report->stepResults as $result) {
            $lines[] = sprintf(
                '  %-20s %-12s %6d %7.2fs',
                $result->stepName,
                strtoupper($result->status->value),
                $result->filesChanged,
                $result->durationSeconds,
            );
            if ($result->errorMessage) {
                $lines[] = "    ERROR: {$result->errorMessage}";
            }
        }

        $lines[] = str_repeat('-', 60);

        if (!empty($report->manualReviewFiles)) {
            $lines[] = '';
            $lines[] = '  MANUAL REVIEW REQUIRED:';
            foreach ($report->manualReviewFiles as $file => $reason) {
                $lines[] = "    - {$file}: {$reason}";
            }
        }

        $lines[] = '';
        return implode("\n", $lines) . "\n";
    }
}
