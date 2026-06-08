<?php

declare(strict_types=1);

namespace App\Application\Service;

use Symfony\Component\Yaml\Yaml;

/**
 * Loads and validates YAML configuration for the conversion process.
 */
class ConfigurationLoader
{
    private const REQUIRED_KEYS = ['source'];

    private const DEFAULTS = [
        'source' => ['path' => './workspace/input'],
        'output' => ['path' => './workspace/output'],
        'backup' => ['enabled' => true, 'strategy' => 'zip', 'path' => './workspace/backups'],
        'report' => ['enabled' => true, 'format' => 'console', 'path' => './workspace/reports'],
        'class_naming' => ['separator' => '_', 'transforms' => [], 'reserved_word_fixes' => [
            'Abstract' => 'Abstracts',
            'Interface' => 'Interfaces',
            'Trait' => 'Traits',
            'Class' => 'Classes',
            'List' => 'Lists',
        ]],
        'namespaces' => [],
        'scan_directories' => [],
        'ignore' => ['patterns' => ['vendor/', 'node_modules/', '.git/'], 'files' => []],
        'includes' => ['remove_class_includes' => true, 'preserve' => ['vendor/autoload.php']],
        'pipeline' => [
            'steps' => ['analyze', 'backup', 'remove_includes', 'replace_references', 'rename_files', 'add_namespaces', 'generate_composer', 'composer_dump'],
            'git_commit_per_step' => false,
            'stop_on_error' => true,
        ],
        'dry_run' => false,
        'logging' => ['level' => 'info', 'file' => 'conversion.log'],
    ];

    /**
     * Load configuration from YAML file, merged with defaults.
     *
     * @return array<string, mixed>
     */
    public function load(?string $configPath = null): array
    {
        $config = self::DEFAULTS;

        if ($configPath !== null && file_exists($configPath)) {
            $userConfig = Yaml::parseFile($configPath);
            $config = $this->mergeRecursive($config, $userConfig);
        }

        return $config;
    }

    /**
     * Override specific config values from CLI arguments.
     *
     * @param array<string, mixed> $config
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public function applyOverrides(array $config, array $overrides): array
    {
        if (isset($overrides['target-path'])) {
            $config['source']['path'] = $overrides['target-path'];
        }
        if (isset($overrides['export-path'])) {
            $config['output']['path'] = $overrides['export-path'];
        }
        if (isset($overrides['dry-run'])) {
            $config['dry_run'] = $overrides['dry-run'];
        }
        if (isset($overrides['steps'])) {
            $config['pipeline']['steps'] = $overrides['steps'];
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     * @throws \InvalidArgumentException
     */
    public function validate(array $config): void
    {
        $sourcePath = $config['source']['path'] ?? null;
        if ($sourcePath === null || !is_dir($sourcePath)) {
            throw new \InvalidArgumentException(
                "Source path \"{$sourcePath}\" does not exist or is not a directory."
            );
        }
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function mergeRecursive(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeRecursive($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }
}
