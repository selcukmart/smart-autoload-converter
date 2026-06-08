<?php
/**
 * @author Selcuk Mart
 * 5.08.2022
 * 14:41
 */

namespace App\Converter\Helpers\ClassOperations;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Helpers\FileContentAnalysis;

class InvestigateMoreSameClassname
{

    /**
     * @var string
     * @author Selcuk Mart
     * 5.08.2022
     * 15:09
     */
    private string $base_file;

    public function __construct(
        private readonly FileObject $processing_fileObject,
        private readonly string     $classname
    )
    {
    }

    public function run(): string
    {
        return $this->prepareNamespacedClassInformationAfterChekingInclude();
    }

    private function prepareNamespacedClassInformationAfterChekingInclude(): string
    {
        if ($this->checkInclude()) {
            $fileObject = FileObject::getInstance($this->base_file);
            $file_analyse = FileContentAnalysis::getInstance($fileObject);
            $class_information = $file_analyse->getClassInformation();
            return $class_information['psr4'] ?? '';
        }
        return '';
    }

    private function checkInclude(): bool
    {
        $classname = $this->classname . ".php";
        $content = $this->processing_fileObject->getOriginalContent();
        $this->base_file = $this->determineInclude($content, $classname);
//        if (
//            str_contains($this->processing_fileObject->getBaseFile(), '/legacy-project/admindesk/trainings/dates/extendedSearch/search.php')
//            && $classname === 'viewController.php') {
//            dump($this->base_file);
//            //exit;
//
//        }
        if (!empty($this->base_file)) {
            return $this->base_file;
        }
        $dir = dirname($this->processing_fileObject->getBaseFile());
        $return = str_contains($content, $classname);

        if (!$return) {
            $dir_file_list = scandir(AutoloadConverterBuilder::getInstance()->getProjectDirectory() . $dir, SCANDIR_SORT_NONE);
            $return = in_array($classname, $dir_file_list, true);
            if (!$return) {
                $explode = explode('/', $dir);
                unset($explode[count($explode) - 1]);
                $dir = implode('/', $explode);
                //dump($dir);
                $dir_file_list = scandir(AutoloadConverterBuilder::getInstance()->getProjectDirectory() . $dir, SCANDIR_SORT_NONE);
                //dump($dir_file_list);
                $return = in_array($classname, $dir_file_list, true);
            }
        }
        if ($return) {
            $this->base_file = $dir . '/' . $classname;
        }
        return $return;
    }

    private function determineInclude($content, $classname): ?string
    {
        return $this->analyseInclude($this->getInclude($content, $classname), $classname);
    }

    /**
     * https://regex101.com/r/or1kiC/1
     * @param $content
     * @param $classname4regex
     * @return array
     * @author Selcuk Mart
     * 8.08.2022
     * 14:27
     */
    private function getInclude($content, $classname): array
    {
        $classname4regex = str_replace(['.', '_'], ['\.', '\_'], $classname);
        $controls = [
            "@include\s+\(('\S+$classname4regex')\)\;@mi",
            "@include\s+'(\S+$classname4regex)'\;@mi",
            '@include\s+\("(\S+' . $classname4regex . ')"\)\;@mi',
            '@include\s+"(\S+' . $classname4regex . ')"\;@mi',
            '@include\s+"(' . $classname4regex . ')"\;@mi',
            "@include\s+'($classname4regex)'\;@mi",
            "@include\_once\s+'\((\S+$classname4regex)\)'\;@mi",
            "@include\_once\s+'(\S+$classname4regex)'\;@mi",
            '@include\_once\s+"\((\S+' . $classname4regex . ')\)"\;@mi',
            '@include\_once\("(' . $classname4regex . ')"\)\;@mi',
            '@include\_once\s+"(\S+' . $classname4regex . ')"\;@mi',
            '@include\_once\s+"(' . $classname4regex . ')"\;@mi',
            '@include\_once"(' . $classname4regex . ')"\;@mi',
        ];
        $detecteds = [];
        foreach ($controls as $control) {
            //dump($control);
            preg_match($control, $content, $matches);
            if (!empty($matches[1]) && !in_array($matches[1], $detecteds, true)) {
                $detecteds[] = $matches[1];
            }
        }
//        if (str_contains($this->processing_fileObject->getBaseFile(), '/legacy-project/promoterpages/tasks/proofOfPerformance/viewController.php')) {
//            dump($this->processing_fileObject->getBaseFile());
//            dump($detecteds);
//        }
        return $detecteds;
    }

    private function analyseInclude($detecteds, $classname): string
    {
        if (empty($detecteds)) {
            return '';
        }
        $latest_count = 10000;
        $latest_detected = '';
        $searched_class_explode = [];
        foreach ($detecteds as $detected) {
            $explode = explode('/', $detected);
            $count = count($explode);
            if ($count < $latest_count) {
                $latest_detected = $detected;
                $latest_count = $count;
                $searched_class_explode = $explode;
            }
        }

        if ($latest_detected !== '') {
            $back = 1;
//            if (str_contains($this->processing_fileObject->getBaseFile(), '/legacy-project/admindesk/trainings/dates/extendedSearch/search.php')) {
//                dump(explode('/', '../viewController.php'));
//                dump($searched_class_explode);
//                dump($latest_detected);
//
//            }
            foreach ($searched_class_explode as $key => $item) {
                if ($item === '..') {
                    $back++;
                    unset($searched_class_explode[$key]);
                }
            }

            $base_file = $this->processing_fileObject->getBaseFile();
            $explode = explode('/', $base_file);
            for ($i = 0; $i < $back; $i++) {
                unset($explode[count($explode) - 1]);
            }

            $less = implode('/', $explode);
            return $less . '/' . $classname;
        }
    }

}