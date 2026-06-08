<?php
/**
 * @author Selcuk Mart
 * 11.08.2022
 * 09:08
 */

namespace App\Converter\Directions\DirectionOperations;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Directions\ChangeFiles;
use function App\Helper\c;

class RemoveOldClassLibraryFolderCreateNewOnes
{


    private string $base_dir;

    public function __construct()
    {
        $this->base_dir = ChangeFiles::getInstance()->getToFolderTemp();
    }

    public function clean(): void
    {
        foreach (AutoloadConverterBuilder::getInstance()->getClassLibraries() as $old_library => $new_library) {
            $this->removeUnnecessaryFileAndFolder($this->base_dir, $old_library);
        }
    }

    private function removeUnnecessaryFileAndFolder($dir, $file_path): void
    {
        $removal_file_path = str_replace('//', '/', $dir . '/' . $file_path);
        if (!is_dir($removal_file_path)) {
            return;
        }
        foreach (scandir($removal_file_path, SCANDIR_SORT_NONE) as $item) {
            if ($item !== '.' && $item !== '..') {
                $file_or_dir = $removal_file_path . '/' . $item;
                if (is_file($file_or_dir)) {
                    unlink($file_or_dir);
                } else {
                    $this->removeUnnecessaryFileAndFolder($removal_file_path, $item);
                }
            }
        }
        if (is_dir($removal_file_path)) {
            rmdir($removal_file_path);
        }
    }
}