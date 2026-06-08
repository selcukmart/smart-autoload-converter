<?php

declare(strict_types=1);

namespace App\Domain\Pipeline\Step;

use App\Infrastructure\Composer\ComposerJsonEditor;
use App\Domain\Pipeline\Contract\PipelineStepInterface;
use App\Domain\Pipeline\Model\PipelineContext;
use App\Domain\Pipeline\Model\PipelineStepStatus;
use App\Domain\Pipeline\Model\StepResult;

/**
 * Step 7: Update composer.json with PSR-4 autoload entries.
 */
class GenerateComposerStep implements PipelineStepInterface
{
    public function __construct(
        private readonly ComposerJsonEditor $composerEditor,
    ) {}

    public function getName(): string { return 'generate_composer'; }
    public function getPriority(): int { return 700; }
    public function supports(PipelineContext $context): bool { return true; }

    public function execute(PipelineContext $context): StepResult
    {
        $startTime = microtime(true);

        // Build PSR-4 mappings from config namespaces
        $configNamespaces = $context->getConfigValue('namespaces', []);
        $psr4Mappings = [];

        foreach ($configNamespaces as $entry) {
            if (isset($entry['namespace'], $entry['path'])) {
                $psr4Mappings[$entry['namespace']] = $entry['path'];
            }
        }

        // Auto-detect from conversion rules if no explicit config
        if (empty($psr4Mappings)) {
            $psr4Mappings = $this->detectFromRules($context);
        }

        $composerJsonPath = rtrim($context->outputPath, '/') . '/composer.json';

        if ($context->dryRun) {
            return new StepResult(
                stepName: $this->getName(),
                status: PipelineStepStatus::Completed,
                changes: [
                    'psr4_entries' => $psr4Mappings,
                    'composer_json' => $composerJsonPath,
                    'dry_run' => true,
                ],
                durationSeconds: microtime(true) - $startTime,
            );
        }

        $result = $this->composerEditor->addPsr4Entries($composerJsonPath, $psr4Mappings);
        $this->composerEditor->write($composerJsonPath, $result['modified']);

        return new StepResult(
            stepName: $this->getName(),
            status: PipelineStepStatus::Completed,
            filesChanged: 1,
            changes: $result['diff'],
            durationSeconds: microtime(true) - $startTime,
        );
    }

    /**
     * Auto-detect PSR-4 root namespaces from conversion rules.
     *
     * @return array<string, string> namespace => relative path
     */
    private function detectFromRules(PipelineContext $context): array
    {
        $roots = [];

        foreach ($context->getConversionRules() as $rule) {
            if (empty($rule->newNamespace)) {
                continue;
            }

            $parts = explode('\\', $rule->newNamespace);
            $rootNamespace = $parts[0];
            $rootDir = strtolower($rootNamespace) . '/';

            if (!isset($roots[$rootNamespace])) {
                $roots[$rootNamespace] = $rootDir;
            }
        }

        return $roots;
    }
}
