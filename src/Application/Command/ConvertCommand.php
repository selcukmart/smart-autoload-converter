<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Service\ConversionOrchestrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'smart:convert',
    description: 'Convert a legacy PHP project from include/require to PSR-4 autoloading',
)]
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
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Path to YAML config file', null)
            ->addOption('target-path', 't', InputOption::VALUE_OPTIONAL, 'Path to legacy project', './workspace/input')
            ->addOption('export-path', null, InputOption::VALUE_OPTIONAL, 'Path for converted output', './workspace/output')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview changes without applying')
            ->addOption('steps', 's', InputOption::VALUE_OPTIONAL, 'Comma-separated list of steps to run', null)
            ->addOption('report', 'r', InputOption::VALUE_OPTIONAL, 'Report format: json, html, console', 'console')
            ->addOption('report-output', null, InputOption::VALUE_OPTIONAL, 'Report output file path', null)
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

        if ($dryRun) {
            $io->note('DRY RUN MODE: No files will be modified.');
        }

        $io->info("Target: {$targetPath}");
        $io->info("Export: {$exportPath}");

        try {
            $results = $this->orchestrator->convert($targetPath, $exportPath, $configPath, $dryRun, $steps);

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
