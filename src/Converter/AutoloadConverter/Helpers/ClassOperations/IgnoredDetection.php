<?php
/**
 * @author Selcuk Mart
 * 5.08.2022
 * 10:58
 */

namespace App\Converter\Helpers\ClassOperations;

use App\Converter\AutoloadConverterBuilder;

class IgnoredDetection
{
    private static array $instance = [];
    private array $ignores = [];

    public function __construct(private AutoloadConverterBuilder $autoloadConverterBuilder, private readonly string $file_path)
    {
    }

    /**
     * @return array
     */
    public static function getInstance(AutoloadConverterBuilder $autoloadConverterBuilder, string $file_path): self
    {
        if (empty(self::$instance[$file_path])) {
            self::$instance[$file_path] = new self($autoloadConverterBuilder, $file_path);
        }
        return self::$instance[$file_path];
    }

    public function run(): bool
    {
        //dump($this->file_path);
        if (!isset($this->ignores[$this->file_path])) {
            $this->ignores[$this->file_path] = false;
            $ignored_files = $this->autoloadConverterBuilder->getIgnoredFiles();
            if (in_array($this->file_path, $ignored_files, true)) {
                $this->ignores[$this->file_path] = true;
            } else {
                $ignored_dirs = $this->autoloadConverterBuilder->getIgnoredDirs();
                $dir = dirname($this->file_path);
                if (in_array($dir, $ignored_dirs, true)) {
                    $this->ignores[$this->file_path] = true;
                }
            }
        }
        return $this->ignores[$this->file_path];
    }

}