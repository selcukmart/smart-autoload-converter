<?php
/**
 * @author Selcuk Mart
 * 18.07.2022
 * 15:12
 */

namespace App\Converter\Directions;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Directions\DirectionOperations\CreateNewDirsAndClassFiles;
use App\Converter\Directions\DirectionOperations\MoveFiles;
use App\Converter\Directions\DirectionOperations\RemoveOldClassLibraryFolderCreateNewOnes;
use App\Converter\Helpers\CopyFolderToSelectedFolder;
use function App\Helper\remove_dir_recursive;

class ChangeFiles
{
    private static $instance;
    private string
        $to_folder,
        $to_folder_temp;

    public function __construct()
    {
        $this->to_folder = AutoloadConverterBuilder::getInstance()->getToFolder();
        $this->to_folder_temp = AutoloadConverterBuilder::getInstance()->getToTempFolder();

    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function execute(): void
    {
        $this->removeAllFilesAndDirsFromLatestOutputArea()
            ->copyToTemporaryDir()
            ->removeOldClassLibraryFolderCreateNewOnes()
            ->moveFiles()
            ->createNewDirsAndClassFiles()
            ->moveToRootFolderAndChangeName();
    }

    private function moveToRootFolderAndChangeName(): void
    {
        foreach (AutoloadConverterBuilder::getInstance()->getChangeLibDir() as $dir) {
            $dir_path = $this->getToFolderTemp() . '/' . $dir['from'];
            $new_dir_path = $this->getToFolderTemp() . '/' . $dir['to'];
            rename($dir_path, $new_dir_path);
        }
    }

    private function removeAllFilesAndDirsFromLatestOutputArea(): self
    {
        remove_dir_recursive($this->getToFolderTemp());
        return $this;
    }

    /**
     * @return string
     */
    public function getToFolder(): string
    {
        return $this->to_folder;
    }

    /**
     * @return string
     */
    public function getToFolderTemp(): string
    {
        return $this->to_folder_temp;
    }

    public function copyToTemporaryDir(): self
    {
        (new CopyFolderToSelectedFolder($this->to_folder, $this->to_folder_temp, force: false))->copyFolder();
        return $this;
    }

    private function removeOldClassLibraryFolderCreateNewOnes(): self
    {
        (new RemoveOldClassLibraryFolderCreateNewOnes())->clean();
        return $this;
    }

    private function moveFiles(): self
    {
        (new MoveFiles())->move();
        return $this;
    }

    private function createNewDirsAndClassFiles(): self
    {
        (new CreateNewDirsAndClassFiles())->create();
        return $this;
    }
}