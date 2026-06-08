<?php

declare(strict_types=1);

namespace App\Infrastructure\Composer;

use App\Domain\FileSystem\Exception\FileSystemException;

/**
 * Reads and modifies composer.json autoload configuration.
 */
class ComposerJsonEditor
{
    /**
     * Add PSR-4 autoload entries to composer.json.
     *
     * @param array<string, string> $psr4Mappings namespace => directory
     * @return array{original: string, modified: string, diff: array}
     */
    public function addPsr4Entries(string $composerJsonPath, array $psr4Mappings): array
    {
        if (!file_exists($composerJsonPath)) {
            // Create a fresh composer.json with PSR-4 entries
            return $this->createFresh($composerJsonPath, $psr4Mappings);
        }

        $original = file_get_contents($composerJsonPath);
        $data = json_decode($original, true, 512, JSON_THROW_ON_ERROR);

        $before = $data['autoload']['psr-4'] ?? [];

        foreach ($psr4Mappings as $namespace => $directory) {
            // Ensure namespace ends with backslash (PSR-4 convention)
            $ns = rtrim($namespace, '\\') . '\\';
            $data['autoload']['psr-4'][$ns] = $directory;
        }

        // Remove any classmap entries that are now covered by PSR-4
        if (isset($data['autoload']['classmap'])) {
            foreach ($psr4Mappings as $directory) {
                $data['autoload']['classmap'] = array_filter(
                    $data['autoload']['classmap'],
                    fn(string $entry) => !str_starts_with($entry, $directory),
                );
            }
            if (empty($data['autoload']['classmap'])) {
                unset($data['autoload']['classmap']);
            }
        }

        $after = $data['autoload']['psr-4'] ?? [];

        $modified = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

        return [
            'original' => $original,
            'modified' => $modified,
            'diff' => [
                'added' => array_diff_key($after, $before),
                'total_entries' => count($after),
            ],
        ];
    }

    /**
     * Write modified composer.json back to disk.
     */
    public function write(string $composerJsonPath, string $content): void
    {
        $dir = dirname($composerJsonPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($composerJsonPath, $content);
    }

    /**
     * @return array{original: string, modified: string, diff: array}
     */
    private function createFresh(string $path, array $psr4Mappings): array
    {
        $psr4 = [];
        foreach ($psr4Mappings as $namespace => $directory) {
            $psr4[rtrim($namespace, '\\') . '\\'] = $directory;
        }

        $data = [
            'autoload' => ['psr-4' => $psr4],
            'require' => ['php' => '>=8.0'],
        ];

        $modified = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

        return [
            'original' => '',
            'modified' => $modified,
            'diff' => [
                'created' => true,
                'added' => $psr4,
                'total_entries' => count($psr4),
            ],
        ];
    }
}
