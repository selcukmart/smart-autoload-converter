<?php
/**
 * @author Selcuk Mart
 * 18.08.2022
 * 11:42
 */

namespace App\Converter\Directions\HelperTraits;

use App\Converter\Helpers\RegexDefinitions;
use function App\Helper\c;


trait DetermineIncludesAndRequiresTrait
{
    private static string $includes_and_requires_type = 'INCLUDES_REQUIRES';

    public static function getIncludesAndRequiresType(): string
    {
        return self::$includes_and_requires_type;
    }

    private function determineIncludesAndRequires(): void
    {
        $this->prepareFileListsArray($this->base_file_or_dir, self::$includes_and_requires_type);

        $this->original_content = $this->FileObject->getOriginalContent();
        $found_matches = [];
        preg_match_all(RegexDefinitions::INCLUDES_GENERAL_CAPTURE_REGEX, $this->original_content, $matches);
        $matched_files = $matches[count($matches) - 1];
        $key = 'require_once';
        if (!empty($matched_files)) {
            foreach ($matched_files as $matched_file_original) {
                $matched_file = $this->getMatchedFileStr($matched_file_original);
                if (!empty($matched_file)
                    && !in_array($matched_file, $found_matches, true)) {
                    if (!isset($this->file_list_ingredients_classes[$this->base_file_or_dir][self::$includes_and_requires_type][$key])) {
                        $this->file_list_ingredients_classes[$this->base_file_or_dir][self::$includes_and_requires_type][$key] = [];
                    }
                    $found_matches[] = $matched_file;
                    $this->file_list_ingredients_classes[$this->base_file_or_dir][self::$includes_and_requires_type][$key][] = $matched_file_original;
                }
            }
        }
        if (empty($this->file_list_ingredients_classes[$this->base_file_or_dir][self::$includes_and_requires_type])) {
            unset($this->file_list_ingredients_classes[$this->base_file_or_dir][self::$includes_and_requires_type]);
        }
    }

    private function getMatchedFileStr(string $matched_file): string
    {
        $matched_file = trim(str_replace('dirname(__FILE__)', '', $matched_file));
        $matched_file = trim(str_replace('__DIR__', '', $matched_file));
        $matched_file = trim(ltrim(ltrim($matched_file, '.'), '/'));
        $matched_file_array = explode(';', str_replace(['"', "'", '(', ')'], '', $matched_file), 2);
        return $matched_file_array[0];
    }
}