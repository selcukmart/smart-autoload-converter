<?php
/**
 * @author Selcuk Mart
 * 18.08.2022
 * 10:35
 */

namespace App\Converter\Directions\DirectionOperations;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Directions\AnalyzeOfFiles;
use App\Converter\Helpers\ClassOperations\FileObject;
use App\Converter\Helpers\RegexDefinitions;
use function App\Helper\c;

class DetermineIncludeRequireAreas
{

    private array|null $class_information;

    private bool $script_is_class;

    private array $change_includes_to;

    public function __construct()
    {
    }

    public function clearClassIncludes(): void
    {
        $this->change_includes_to = AutoloadConverterBuilder::getInstance()->getChangeIncludesTo();
        $getClearClassIncludesRequires = AutoloadConverterBuilder::getInstance()->getClearClassIncludesRequires();

        if ($getClearClassIncludesRequires['active']) {
            $file_list_ingredients_classes = AutoloadConverterBuilder::getInstance()->getFileListIngredientsClasses();
            $type = AnalyzeOfFiles::getIncludesAndRequiresType();
            foreach ($file_list_ingredients_classes as $file => $file_list_ingredients_class) {
                $class_information = FileObject::getInstance($file)->getClassInformation();
                if (!empty($class_information)) {
                    $this->removeIncludeAndRequiresFromClassFile($file, '');
                }
                if (!empty($file_list_ingredients_class[$type])) {
                    $continue = $this->isInValidDir($getClearClassIncludesRequires['dirs'], $file);

                    if ($continue) {
                        continue;
                    }
                    $file_dir = dirname($file);
                    foreach ($file_list_ingredients_class[$type] as $sub_type => $scripts) {
                        foreach ($scripts as $script) {
                            if (preg_match('/\$/', $script)) {
                                continue;
                            }
                            if (!preg_match('/\.php$/', $script)) {
                                continue;
                            }

                            $this->removeIncludeRequireFromFile($file_dir, $script, $file);
                            $this->changePathOfTheScript($file_dir, $script, $file);
                        }
                    }
                }
            }
        }
    }

    private function changePathOfTheScript($file_dir, $script, $file): void
    {
        if (!$this->script_is_class) {
            if (isset($this->change_includes_to[$script])) {
                $new_path = $this->change_includes_to[$script];
                $explode = explode('/', $file, -1);
                $count = count($explode) - 1;
                $up_path = str_repeat('../', $count);
                $new_path_with_up_path = "require_once( __DIR__ . '/" . rtrim($up_path, '/') . $new_path . "');";
                $regex = str_replace(['{{SCRIPT}}', '.'], [$script, '\.'], RegexDefinitions::INCLUDES_GENERAL_CAPTURE_REPLACE_REGEX);
                $original_content = FileObject::getInstance($file)->getOriginalContent();
                $original_content = preg_replace($regex, $new_path_with_up_path, $original_content, $limit = -1, $count);
                FileObject::getInstance($file)
                    ->setOriginalContent($original_content)
                    ->writeOriginalContent();
            }
        }
    }

    private function isInValidDir($dirs, int|string $file): bool
    {
        $continue = true;
        foreach ($dirs as $dir) {
            if (str_contains($file, $dir)) {
                $continue = false;
                break;
            }
        }
        return $continue;
    }

    private function getFullPath(string $file_dir, mixed $script): string
    {
        $script = ltrim(trim($script), '/');
        $paths = explode('/', $script);
        $up_count = 0;
        foreach ($paths as $key => $path) {
            if ($path === '..') {
                $up_count++;
                unset($paths[$key]);
            }
        }
        $script = implode('/', $paths);
        $file_dir_array = explode('/', $file_dir);
        $file_dir = implode('/', array_slice($file_dir_array, 0, count($file_dir_array) - $up_count));
        return $file_dir . '/' . $script;
    }


    private function getClassInformation(string $full_path, $script): ?array
    {
        $this->class_information = FileObject::getInstance($full_path)->getClassInformation();
        if (empty($this->class_information)) {
            foreach (AutoloadConverterBuilder::getInstance()->getAllclassesInProject() as $class => $class_info) {
                if (str_contains($class_info['file_path'], $script)) {
                    $this->class_information = $class_info;
                    break;
                }
            }
        }
        return $this->class_information;
    }

    private function removeIncludeRequireFromFile(string $file_dir, string $script, string $file): void
    {
        $full_path = $this->getFullPath($file_dir, $script);
        $class_information = $this->getClassInformation($full_path, $script);
        $this->script_is_class = false;
        if (isset($class_information['type'])) {
            $this->script_is_class = true;
            $this->removeIncludeAndRequiresFromClassFile($file, $script);
        }
    }

    private function removeIncludeAndRequiresFromClassFile(string $file, $script): void
    {
        $original_content = FileObject::getInstance($file)->getOriginalContent();
        if (empty($script)) {
            $regex = RegexDefinitions::INCLUDES_GENERAL_REGEX;
        } else {
            $regex = str_replace(['{{SCRIPT}}', '.'], [$script, '\.'], RegexDefinitions::INCLUDES_GENERAL_CAPTURE_REPLACE_REGEX);
        }
        $original_content = preg_replace($regex, '', $original_content, $limit = -1, $count);
        FileObject::getInstance($file)
            ->setOriginalContent($original_content)
            ->writeOriginalContent();
    }

}