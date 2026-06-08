<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Application\Command;

use SmartAutoloadConverter\Application\Service\ConversionOrchestrator;
use SmartAutoloadConverter\Domain\Pipeline\Model\PipelineContext;
use SmartAutoloadConverter\Domain\Report\Exporter\ConsoleExporter;
use SmartAutoloadConverter\Domain\Report\Exporter\HtmlExporter;
use SmartAutoloadConverter\Domain\Report\Exporter\JsonExporter;
use SmartAutoloadConverter\Domain\Report\Service\ReportGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'convert', description: 'Convert a legacy PHP project from include/require to PSR-4 autoloading')]
class ConvertCommand extends Command
{
    public function __construct(
        private readonly ConversionOrchestrator $orchestrator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Path to YAML config file')
            ->addOption('target-path', 't', InputOption::VALUE_REQUIRED, 'Path to legacy project')
            ->addOption('export-path', null, InputOption::VALUE_OPTIONAL, 'Path for converted output', './output')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview changes without applying')
            ->addOption('steps', 's', InputOption::VALUE_OPTIONAL, 'Comma-separated list of steps to run')
            ->addOption('report', 'r', InputOption::VALUE_OPTIONAL, 'Report format: json, html, console', 'console')
            ->addOption('report-output', null, InputOption::VALUE_OPTIONAL, 'Save report to file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Smart Autoload Converter');

        $dryRun = (bool) $input->getOption('dry-run');
        $targetPath = $input->getOption('target-path');
        $exportPath = $input->getOption('export-path');
        $configPath = $input->getOption('config');
        $steps = $input->getOption('steps') ? explode(',', $input->getOption('steps')) : null;
        $reportFormat = $input->getOption('report');
        $reportOutput = $input->getOption('report-output');

        if ($dryRun) {
            $io->note('DRY RUN MODE: No files will be modified.');
        }

        $io->info("Target: {$targetPath}");
        $io->info("Export: {$exportPath}");

        try {
            $results = $this->orchestrator->convert($targetPath, $exportPath, $configPath, $dryRun, $steps);

            // Step summary table
            $rows = [];
            foreach ($results as $result) {
                $rows[] = [
                    $result->stepName,
                    $result->status->value,
                    $result->filesChanged,
                    $result->errorMessage ?? '-',
                    number_format($result->durationSeconds, 2) . 's',
                ];
            }
            $io->table(['Step', 'Status', 'Files', 'Error', 'Duration'], $rows);

            // Generate report
            $reportGen = new ReportGenerator();
            $context = new PipelineContext($targetPath, $exportPath, [], $dryRun);
            $report = $reportGen->generate($context, $results);

            $exporter = match ($reportFormat) {
                'json' => new JsonExporter(),
                'html' => new HtmlExporter(),
                default => new ConsoleExporter(),
            };
            $exported = $exporter->export($report);

            if ($reportOutput) {
                file_put_contents($reportOutput, $exported);
                $io->info("Report saved to: {$reportOutput}");
            }

            // Also save JSON for smart:report to consume later
            $jsonPath = rtrim($exportPath, '/') . '/conversion-report.json';
            @mkdir(dirname($jsonPath), 0755, true);
            file_put_contents($jsonPath, (new JsonExporter())->export($report));

            $failed = array_filter($results, fn($r) => $r->status->value === 'failed');
            if (count($failed) > 0) {
                $io->error(count($failed) . ' step(s) failed.');
                return Command::FAILURE;
            }

            $io->success('Conversion complete.');
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
