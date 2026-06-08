<?php
/**
 * @author selcukmart
 * 14.03.2022
 * 18:10
 */

namespace App\Converter\BuilderTraits;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Directions\ChangeFileContentsAfterCreating;
use App\Converter\Directions\ChangeFiles;
use App\Converter\Directions\AnalyzeOfFiles;
use App\Converter\Directions\RemoveOrChangeRequireIncludeRows;
use App\Converter\Directions\SearchAndReplaceInFiles;
use App\Converter\Directions\ZipWholeFolder;
use App\Helper\ServiceHelper;
use JsonException;
use function App\Helper\c;
use function App\Helper\remove_dir_recursive;

trait BuilderDirectionsTrait
{

    private array
        $file_list;


    public function createZip(): self
    {
        (new ZipWholeFolder($this))->createZip();
        return $this;
    }


    /**
     * Change Filename
     * Change Classname In file
     * Add namespace to file
     * Remove require row from class file, if used in a file
     * @return self
     * @author Selcuk Mart
     * 18.07.2022
     * 16:12
     */
    public function changeFiles(): self
    {
        if (!is_null(ServiceHelper::getRequest()->get('create-files'))) {
            ChangeFiles::getInstance()->execute();
        }
        return $this;
    }

    /**
     * Detect unused classes and prepare a list as json
     * @return self
     * @author Selcuk Mart
     * 18.07.2022
     * 16:14
     */
    public function SearchAndReplaceInFiles(): self
    {
        if (
            !is_null(ServiceHelper::getRequest()->get('create-files'))
            || !is_null(ServiceHelper::getRequest()->get('make-changes-in-files'))
        ) {
            (new SearchAndReplaceInFiles($this))->replaceClass();
        }
        return $this;
    }

    /**
     * @throws JsonException
     */
    public function analyzeOfFiles(): self
    {
        (new AnalyzeOfFiles($this))->analyzeFiles();
        return $this;
    }

    public function removeOrChangeRequireIncludeRows(): self
    {
        (new RemoveOrChangeRequireIncludeRows())->execute();
        return $this;
    }

    public function changeFileContents(): self
    {
        if (!is_null(ServiceHelper::getRequest()->get('create-files'))) {
            (new ChangeFileContentsAfterCreating())->execute();
        }
        return $this;
    }

    public function runComposerDumpAutoloadCommand(): self
    {
        if (!is_null(ServiceHelper::getRequest()->get('create-files'))) {
            echo(shell_exec('cd ' . AutoloadConverterBuilder::getInstance()->getToTempFolder() . ' && composer dump-autoload'));
        }
        return $this;
    }

    public function removeUnnecessaryFiles(): self
    {
        $output_folder = AutoloadConverterBuilder::getInstance()->getToTempFolder();
        foreach ($this->getSettings('remove_unnecessary_files_folders') as $folders_and_file) {
            $folders_and_file = realpath($output_folder.$folders_and_file);
            if (is_file($folders_and_file)) {
                unlink($folders_and_file);
            } else {
                remove_dir_recursive($folders_and_file);
            }
        }
        return $this;
    }

    public function copyNecessaryFilesFolder(): self
    {
        $from_folder = AutoloadConverterBuilder::getInstance()->getToFolder();
        $to_folder = AutoloadConverterBuilder::getInstance()->getToTempFolder();
        foreach ($this->getSettings('copy_necessary_files_folders') as $from => $to) {
            $from = realpath($from_folder.$from);
            if (is_file($from)) {
                $dir = dirname($to_folder .  $to);
                if(!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
                }
                copy($from, $to_folder .  $to);
            } else {
                $this->recurseCopy($from, $to_folder . $to);
            }
        }
        return $this;
    }

    public function recurseCopy($src, $dst): void
    {
        $dir = opendir($src);
        if (!mkdir($dst) && !is_dir($dst)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dst));
        }
        while (false !== ($file = readdir($dir))) {
            if (($file !== '.') && ($file !== '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recurseCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public function getFileList(): array
    {
        return $this->file_list;
    }

    public function setFileList(array $file_list): void
    {
        $this->file_list = $file_list;
    }




}