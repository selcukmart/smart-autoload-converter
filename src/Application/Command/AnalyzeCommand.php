<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Service\ConfigurationLoader;
use App\Domain\Analysis\Service\ClassAnalyzer;
use App\Domain\Analysis\Service\DependencyGraphBuilder;
use App\Domain\Conversion\Service\ClassNameTransformer;
use App\Domain\FileSystem\Service\FileScanner;
use App\Domain\FileSystem\Service\FileWriter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'smart:analyze',
    description: 'Analyze a legacy PHP project without making any changes',
)]
class AnalyzeCommand extends Command
{
    public function __construct(
        private readonly ConfigurationLoader $configLoader,
        private readonly FileScanner $fileScanner,
        private readonly DependencyGraphBuilder $graphBuilder,
        private readonly ClassNameTransformer $transformer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Path to YAML config file')
            ->addOption('target-path', 't', InputOption::VALUE_OPTIONAL, 'Path to legacy project', './workspace/input')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: table, json', 'table')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Smart Autoload Converter - Analysis');

        $targetPath = $input->getOption('target-path');
        $configPath = $input->getOption('config');
        $format = $input->getOption('format');

        $config = $this->configLoader->load($configPath);
        $config = $this->configLoader->applyOverrides($config, ['target-path' => $targetPath]);
        $this->configLoader->validate($config);

        $sourcePath = $config['source']['path'];
        $io->info("Scanning: {$sourcePath}");

        // Scan
        $files = $this->fileScanner->scan(
            $sourcePath,
            $config['ignore']['patterns'] ?? [],
            $config['ignore']['files'] ?? [],
        );

        $io->info("Found " . count($files) . " PHP files");

        // Build dependency graph
        $graph = $this->graphBuilder->build($files);

        if ($format === 'json') {
            $output->writeln(json_encode([
                'total_files' => $graph->getTotalFiles(),
                'total_classes' => $graph->getTotalClasses(),
                'total_includes' => $graph->getTotalIncludes(),
                'unused_classes' => $graph->getUnusedClasses(),
                'duplicate_names' => $graph->getDuplicateNames(),
            ], JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        // Table output
        $io->section('Summary');
        $io->table(
            ['Metric', 'Count'],
            [
                ['PHP Files', $graph->getTotalFiles()],
                ['Class Definitions', $graph->getTotalClasses()],
                ['Include/Require Statements', $graph->getTotalIncludes()],
                ['Unused Classes', count($graph->getUnusedClasses())],
                ['Duplicate Names', count($graph->getDuplicateNames())],
            ],
        );

        // Class list
        $io->section('Classes Found');
        $rows = [];
        foreach ($graph->getAllClassDefinitions() as $name => $def) {
            $rule = $this->transformer->transform($name);
            $rows[] = [$name, $def->type->value, $rule->newFullyQualifiedName, basename($def->filePath)];
        }
        $io->table(['Original Name', 'Type', 'New FQCN', 'File'], $rows);

        // Duplicate names warning
        $dupes = $graph->getDuplicateNames();
        if (!empty($dupes)) {
            $io->section('Duplicate Class Names (require resolution)');
            foreach ($dupes as $shortName => $originals) {
                $io->warning("{$shortName}: " . implode(', ', $originals));
            }
        }

        $io->success('Analysis complete. No files were modified.');
        return Command::SUCCESS;
    }
}
