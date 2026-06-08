<?php
/**
 * @author Selcuk Mart
 * 10.08.2022
 * 14:59
 */

namespace App\Converter\Directions\HelperTraits;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Helpers\ClassOperations\UseDetection;
use App\Converter\Helpers\FileContentAnalysis;
use App\Converter\Helpers\RegexDefinitions;
use function App\Helper\c;

trait ClassProcessTrait
{
    private function useToClassname(string $class_name): string
    {
        return (UseDetection::getInstance($class_name, $this->base_file_or_dir, $this->content, $this->isClass()))->run();
    }

    private function determineClassInMethods(): void
    {
        preg_match_all(RegexDefinitions::FUNCTION_REGEX, $this->content, $function_matches);
        $matched_classes = [];
        foreach ($function_matches[count($function_matches) - 1] as $function_name) {
            $function_name = trim($function_name);
            if (empty($function_name)) {
                continue;
            }
            foreach (explode(',', $function_name) as $parameter) {
                $parameter = trim($parameter);
                if (empty($parameter)) {
                    continue;
                }
                preg_match_all(RegexDefinitions::CLASS_NAME_REGEX, $parameter, $class_in_method_matches);
                //dump($class_in_method_matches);
                if (!empty($class_in_method_matches[count($class_in_method_matches) - 1])) {
                    $matched_classes[] = $class_in_method_matches[count($class_in_method_matches) - 1][0];
                }
            }
        }
        //dump($class_in_method_matches_all);
        $this->addToClassLists($matched_classes, 'CLASS_IN_METHOD');
    }

    private function determineInTypeDefinitionClasses(): void
    {
        preg_match_all(RegexDefinitions::CLASS_IN_TYPE_DEFINITION_REGEX, $this->content, $class_in_type_definition_matches);
        $matched = [];
        $matched_str = '';
        if (!empty($class_in_type_definition_matches[count($class_in_type_definition_matches) - 1][0])) {
            $matched_str = $class_in_type_definition_matches[count($class_in_type_definition_matches) - 1][0];
        } elseif (!empty($class_in_type_definition_matches[count($class_in_type_definition_matches) - 2][0])) {
            $matched_str = $class_in_type_definition_matches[count($class_in_type_definition_matches) - 2][0];
        }
        if (!empty($matched_str)) {
            $matched_str = explode('{', $matched_str, 2)[0];
            $matched_str = trim(str_replace([':', ';'], '', $matched_str));
            if (!in_array($matched_str, $this->exceptions, true)) {
                $matched[] = $matched_str;
            }

        }

        $this->addToClassLists($matched, 'CLASS_IN_TYPE_DEFINITION');
    }

    private function ifItIsClassOperations(): void
    {
        $this->class_information = [];
        if ($this->isClass()) {
            $this->class_information = FileContentAnalysis::getInstance($this->FileObject)->getClassInformation();

            $this->autoloadConverterBuilder->setToAllClassesListInProject($this->class_information);
            $this->determineClassInMethods();
            $this->determineInTypeDefinitionClasses();
        }

    }
}