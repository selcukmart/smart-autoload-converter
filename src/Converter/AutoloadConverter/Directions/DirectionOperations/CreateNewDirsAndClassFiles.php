<?php
/**
 * @author Selcuk Mart
 * 11.08.2022
 * 10:29
 */

namespace App\Converter\Directions\DirectionOperations;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Directions\ChangeFiles;
use App\Converter\Directions\HelperTraits\DirFileOperationsTrait;
use App\Helper\GeneralHelpers;
use App\Helper\ServiceHelper;
use function App\Helper\c;

class CreateNewDirsAndClassFiles
{
    use DirFileOperationsTrait;

    private string
        $base_dir,
        $read_dir;
    /**
     * @var string
     * @author Selcuk Mart
     * 15.08.2022
     * 13:39
     */
    private string $concurrentFile;
    /**
     * @var string
     * @author Selcuk Mart
     * 15.08.2022
     * 13:39
     */
    private string $newFile;
    /**
     * @var string
     * @author Selcuk Mart
     * 15.08.2022
     * 13:39
     */
    private string $concurrentDirectory;
    /**
     * @var string
     * @author Selcuk Mart
     * 15.08.2022
     * 13:40
     */
    private string $newFileDirectory;

    public function __construct()
    {
        $this->read_dir = ChangeFiles::getInstance()->getToFolder();
        $this->base_dir = ChangeFiles::getInstance()->getToFolderTemp();
    }

    public function create(): void
    {
        $files = [];

        foreach (AutoloadConverterBuilder::getInstance()->getAllclassesInProject() as $class_information) {

            if (
                !$this->getToBeMoved($class_information)
                && !$this->getNamespaced($class_information)
                //&& $this->isInReCreatePaths($class_information)
            ) {
                $files[] = [
                    'old_file' => $class_information['file_path'],
                    'new_file' => $class_information['new_file_path'],
                ];
                $this->moveFile($this->read_dir, $this->base_dir, $class_information, $class_information['new_file_path']);
            }
        }
        $request = ServiceHelper::getRequest();

        if (!is_null($request->get('show-created-files'))) {
            (new GeneralHelpers($files))->dump();
        }
    }

    private function getToBeMoved($class_information): bool
    {
        return (bool)$class_information['to_be_moved'];
    }

    private function getNamespaced($class_information): bool
    {
        return (bool)$class_information['namespaced'];
    }

    /**
     * @param $file_path
     * @author Selcuk Mart
     * 15.08.2022
     * 13:36
     */
    private function removeOldFileAndDir($file_path, $new_file_path): void
    {


        if (is_file($this->concurrentFile)) {
            unlink($this->concurrentFile);
        }

        if ($this->concurrentDirectory !== $this->newFileDirectory && is_dir($this->concurrentDirectory)) {
            rmdir($this->concurrentDirectory);
        }
    }

    private function isInReCreatePaths(mixed $class_information)
    {
        $dirs = AutoloadConverterBuilder::getInstance()->getReCreatePaths();
        foreach ($dirs as $dir) {
            $dir_regex = '@^' . $dir . '@';
            if (preg_match($dir_regex, $class_information['file_path'])) {
                return true;
            }
        }
    }
}