<?php
/**
 * @author Selcuk Mart
 * 10.08.2022
 * 15:02
 */

namespace App\Converter\Directions\HelperTraits;

use App\Converter\Helpers\FileContentAnalysis;
use function App\Helper\c;

trait CheckDetermineProcessTrait
{

    private function isEmptyMatched(array $matched_classes): bool
    {
        if (empty($matched_classes)) {
            return true;
        }
        $empty = true;
        $this->clean_class_lists = [];
        foreach ($matched_classes as $matched_class) {
            $matched_class = trim($matched_class);
            if (!empty($matched_class)
                && !in_array($matched_class, $this->exceptions, true)) {
                $empty = false;
                $this->clean_class_lists[] = $matched_class;
            }
        }
        return $empty;
    }

    private function isClass(): bool
    {
        $this->class = $this->file_list_ingredients_classes[$this->base_file_or_dir]['type'] === 'class';
        return $this->class;
    }

    private function analyzeFile(): void
    {

        $this->file_type = FileContentAnalysis::getInstance($this->FileObject)->isClass() ? 'class' : 'script';
        $this->file_list_ingredients_classes[$this->base_file_or_dir]['type'] = $this->file_type;

    }

    private function determineBaseFileOrDir($file_path): string|array
    {
        return str_replace($this->autoloadConverterBuilder->getProjectDirectory(), '', $file_path);
    }

    private function isUnusedLibraryDetectionStarting(): void
    {
        if ($this->isDetectionUnusedClassesActive()) {
            $detect_unused_classes_dirs_check = false;
            $file_path = $this->determineBaseFileOrDir($this->base_file_or_dir);
            foreach ($this->autoloadConverterBuilder->getSettings('library_dirs') as $library_dir) {
                $library_dir = $this->determineBaseFileOrDir($library_dir);
                if (str_contains($file_path, $library_dir)) {
                    $detect_unused_classes_dirs_check = true;
                    break;
                }
            }
            $this->detect_unused_classes = $detect_unused_classes_dirs_check;
        }
    }

    private function isDetectionUnusedClassesActive(): bool
    {
        return true;//$this->autoloadConverterBuilder->getSettings('detect_unused_classes');
    }
}