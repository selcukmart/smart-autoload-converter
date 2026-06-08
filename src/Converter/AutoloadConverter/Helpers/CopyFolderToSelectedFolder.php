<?php
/**
 * @author Selcuk Mart
 * 10.08.2022
 * 09:23
 */

namespace App\Converter\Helpers;

use function App\Helper\c;

class CopyFolderToSelectedFolder
{
    public function __construct(private readonly string $from_folder, private readonly string $to_folder, private bool $force = false)
    {
    }

    public function copyFolder(): void
    {

        $control_file = $this->to_folder . '/.created_by_autoconverter';
        if (!$this->force && is_file($control_file)) {
            return;
        }

        if (!is_dir($this->to_folder)
            && !mkdir($concurrentDirectory = $this->to_folder, 0755, true)
            && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        $this->copyFolderRecursive($this->from_folder, $this->to_folder);
        file_put_contents($control_file, 'true');
    }

    private function copyFolderRecursive(string $from_folder, string $to_folder): void
    {
        foreach (scandir($from_folder, SCANDIR_SORT_NONE) as $file) {
            if ($file !== '.' && $file !== '..') {
                if (is_dir($from_folder . '/' . $file)) {
                    if (!is_dir($to_folder . '/' . $file)) {
                        if (!mkdir($concurrentDirectory = $to_folder . '/' . $file) && !is_dir($concurrentDirectory)) {
                            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                        }
                    }
                    $this->copyFolderRecursive($from_folder . '/' . $file, $to_folder . '/' . $file);
                } else {
                    $content = file_get_contents($from_folder . '/' . $file);
                    file_put_contents($to_folder . '/' . $file, $content);
                    //copy($from_folder . '/' . $file, $to_folder . '/' . $file);
                }
            }
        }
    }

}