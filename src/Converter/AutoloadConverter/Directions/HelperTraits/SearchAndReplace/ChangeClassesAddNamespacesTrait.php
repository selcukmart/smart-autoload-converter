<?php
/**
 * @author Selcuk Mart
 * 6.09.2022
 * 14:29
 */

namespace App\Converter\Directions\HelperTraits\SearchAndReplace;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Helpers\ClassOperations\ChangeClassnameAndAddNamespaceInContent;

trait ChangeClassesAddNamespacesTrait
{

    private function changeClassesAddNamespaces(): void
    {
        foreach (AutoloadConverterBuilder::getInstance()->getAllclassesInProject() as $class_information) {
            $psr4_class_name = $class_information['psr4'];
            $file = $class_information['file_path'];
            $content = $this->getContent($file);
            if (empty($content) || $this->hasUse($psr4_class_name, $content)) {
                continue;
            }
            $content = ChangeClassnameAndAddNamespaceInContent::getInstance($file, $content)->changeClassName();
            $this->setContentToFile($file, $content);
        }
    }

}