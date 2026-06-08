<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Report\Exporter\ConsoleExporter;
use App\Domain\Report\Exporter\HtmlExporter;
use App\Domain\Report\Exporter\JsonExporter;
use App\Domain\Report\Exporter\ReportExporterInterface;
use App\Domain\Report\Model\ConversionReport;
use App\Domain\Pipeline\Model\PipelineStepStatus;
use App\Domain\Pipeline\Model\StepResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'smart:report',
    description: 'Generate a report from a previous conversion JSON log',
)]
class ReportCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('input', InputArgument::REQUIRED, 'Path to conversion result JSON')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: console, json, html', 'console')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file path (default: stdout)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $inputPath = $input->getArgument('input');
        $format = $input->getOption('format');
        $outputPath = $input->getOption('output');

        if (!file_exists($inputPath)) {
            $io->error("Input file not found: {$inputPath}");
            return Command::FAILURE;
        }

        $data = json_decode(file_get_contents($inputPath), true);
        if ($data === null) {
            $io->error('Invalid JSON in input file');
            return Command::FAILURE;
        }

        // Reconstruct StepResults from JSON
        $stepResults = array_map(fn(array $s) => new StepResult(
            stepName: $s['name'],
            status: PipelineStepStatus::from($s['status']),
            filesChanged: $s['files_changed'] ?? 0,
            changes: $s['changes'] ?? [],
            errorMessage: $s['error'] ?? null,
            durationSeconds: $s['duration_seconds'] ?? 0.0,
        ), $data['steps'] ?? []);

        $report = new ConversionReport(
            sourcePath: $data['source_path'] ?? '',
            outputPath: $data['output_path'] ?? '',
            dryRun: $data['dry_run'] ?? false,
            stepResults: $stepResults,
            manualReviewFiles: $data['manual_review_files'] ?? [],
            totalDurationSeconds: $data['total_duration_seconds'] ?? 0.0,
        );

        $exporter = $this->getExporter($format);
        $exported = $exporter->export($report);

        if ($outputPath) {
            file_put_contents($outputPath, $exported);
            $io->success("Report written to: {$outputPath}");
        } else {
            $output->write($exported);
        }

        return Command::SUCCESS;
    }

    private function getExporter(string $format): ReportExporterInterface
    {
        return match ($format) {
            'json' => new JsonExporter(),
            'html' => new HtmlExporter(),
            default => new ConsoleExporter(),
        };
    }
}
