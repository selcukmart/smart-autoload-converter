<?php
/**
 * @author Selcuk Mart
 * 18.07.2022
 * 09:40
 */

namespace App\Converter\Directions;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Directions\HelperTraits\CheckDetermineProcessTrait;
use App\Converter\Directions\HelperTraits\ClassProcessTrait;
use App\Converter\Directions\HelperTraits\DetermineClassnamesTrait;
use App\Converter\Directions\HelperTraits\DetermineIncludesAndRequiresTrait;
use App\Converter\Directions\HelperTraits\PrepareClassDefinitionsTrait;
use App\Converter\Directions\HelperTraits\StoreTrait;
use App\Converter\Helpers\ClassOperations\FileObject;
use App\Converter\Helpers\ClassOperations\IgnoredDetection;
use App\Converter\Helpers\PrepareUnusedLibraries;
use App\Helper\ServiceHelper;
use function App\Helper\c;

class AnalyzeOfFiles extends AbstractDirections
{

    use
        PrepareClassDefinitionsTrait,
        DetermineClassnamesTrait,
        StoreTrait,
        ClassProcessTrait,
        CheckDetermineProcessTrait,
        DetermineIncludesAndRequiresTrait;

    public function analyzeFiles(): void
    {
        $this->setClassTo();
        $this->scanNecessaryFiles();
        $this->outputDataOverRequest();
        $this->setDataToBuilderObject();
        $this->prepareUnusedClasses();
    }

    private function scanFiles($dir): void
    {
        foreach (array_diff(scandir($dir, SCANDIR_SORT_NONE), ['..', '.']) as $list) {
            $this->prepareRootFileOrDir($dir, $list);
            $this->prepareBaseFileOrDir();
            if ($this->isIgnored()) {
                continue;
            }
            if (is_dir($this->root_file_or_dir)) {
                $this->isUnusedLibraryDetectionStarting();
                $this->scanFiles($this->root_file_or_dir);
            } elseif ($this->isValidFile()) {
                $this->setFileTypeAsEmpty();
                $this->createPersistentFileObject();
                $this->removePhpCloseTagFromFile();
                $this->readContent();
                $this->determineIncludesAndRequires();
                $this->analyzeFile();
                $this->ifItIsClassOperations();
                $this->analyzeClassUsageFormsInFile();
            }
        }
    }

    private function removePhpCloseTagFromFile(): void
    {
        $original_content = $this->FileObject->getOriginalContent();
        $original_content = preg_replace(['@\?>$@', '@\?>\s+$@'], '', $original_content);
        $this->FileObject
            ->setOriginalContent($original_content)
            ->writeOriginalContent();
    }

    private function outputDataOverRequest(): void
    {
        if ($this->hasRequest('all-classes')) {
            c('Class Count: ' . count(AutoloadConverterBuilder::getInstance()->getAllclassesInProject()));
            c(AutoloadConverterBuilder::getInstance()->getAllclassesInProject());
        }
        if ($this->hasRequest('class-count')) {
            c(AutoloadConverterBuilder::getInstance()->getClassnameCount());
        }

        if ($this->hasRequest('class-count-coupled')) {
            c(AutoloadConverterBuilder::getInstance()->getClassnameCountCoupled());
        }

        if ($this->hasRequest('file-to-class-list')) {
            c($this->file_list_ingredients_classes);
        }

        if ($this->hasRequest('class-to-file-list')) {
            c($this->class_lists_ingredients_file_dirs);
        }

        if ($this->hasRequest('list-libraries')) {
            c(AutoloadConverterBuilder::getInstance()->getClassLibraries());
        }
    }

    private function createPersistentFileObject(): void
    {
        $this->FileObject = FileObject::getInstance($this->base_file_or_dir);
    }

    private function readContent(): void
    {
        $this->content = $this->FileObject->getContent();
    }

    private function analyzeClassUsageFormsInFile(): void
    {
        $this->determineNewClassMatches();
        $this->determineInstanceofClassMatches();
        $this->determineCatchClassMatches();
        $this->determineExceptionInsideCatchClassMatches();
        $this->determineColonClassMatches();
        $this->determineExtendsClassMatches();
        $this->determineImplementsClassMatches();
    }

    private function prepareRootFileOrDir($dir, mixed $list): void
    {
        $this->root_file_or_dir = $dir . DIRECTORY_SEPARATOR . $list;
    }

    private function prepareBaseFileOrDir(): void
    {
        $this->base_file_or_dir = $this->determineBaseFileOrDir($this->root_file_or_dir);
    }

    private function isIgnored(): bool
    {
        return IgnoredDetection::getInstance(AutoloadConverterBuilder::getInstance(), $this->base_file_or_dir)->run();
    }

    private function isValidFile(): bool
    {
        return str_contains($this->base_file_or_dir, '.php') &&
            !str_contains($this->base_file_or_dir, '/vendor');
    }

    private function setFileTypeAsEmpty(): void
    {
        $this->file_type = '';
    }

    private function setDataToBuilderObject(): void
    {
        AutoloadConverterBuilder::getInstance()
            ->setClassListsIngredientsFileDirs($this->class_lists_ingredients_file_dirs)
            ->setFileListIngredientsClasses($this->file_list_ingredients_classes)
            ->setAllContents($this->content_list);
    }

    private function prepareUnusedClasses(): void
    {
        $unusedLibraries = (new PrepareUnusedLibraries(AutoloadConverterBuilder::getInstance()->getOnlyUsedClassesExceptItselfInProject(),
            AutoloadConverterBuilder::getInstance()->getAllclassesInProject()))
            ->getUnusedLibraries();
        if ($this->hasRequest('unused-libraries')) {
            c($unusedLibraries);
        }
    }

    private function scanNecessaryFiles(): void
    {
        foreach (AutoloadConverterBuilder::getInstance()->getClassChangeDirs() as $dir) {
            $this->dir = AutoloadConverterBuilder::getInstance()->getProjectDirectory() . $dir;
            $this->scanFiles($this->dir);
        }
    }

    private function setClassTo(): void
    {
        $this->class_to = AutoloadConverterBuilder::getInstance()->getClassTo();
    }

    private function hasRequest(string $key): bool
    {
        return !is_null(ServiceHelper::getRequest()->get($key));
    }


}