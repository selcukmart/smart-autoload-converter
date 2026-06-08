<?php
/**
 * @author Selcuk Mart
 * 11.08.2022
 * 08:55
 */

namespace App\Converter\Directions\DirectionOperations;

use App\Converter\AutoloadConverterBuilder;
use App\Converter\Directions\ChangeFiles;
use App\Converter\Directions\HelperTraits\DirFileOperationsTrait;
use App\Helper\GeneralHelpers;
use App\Helper\ServiceHelper;

class MoveFiles
{
    use DirFileOperationsTrait;

    private string
        $read_dir,
        $base_dir;

    public function __construct()
    {
        $this->read_dir = ChangeFiles::getInstance()->getToFolder();
        $this->base_dir = ChangeFiles::getInstance()->getToFolderTemp();
    }

    public function move(): void
    {
        $files = [];
        foreach (AutoloadConverterBuilder::getInstance()->getAllclassesInProject() as $class_information) {
            if ($class_information['to_be_moved']) {
                $files[] = [
                    'old_file' => $class_information['file_path'],
                    'new_file' => $class_information['new_file_path'],
                ];
                $this->moveFile($this->read_dir, $this->base_dir, $class_information, $class_information['to_be_moved_dir']);
            }
        }
        $request = ServiceHelper::getRequest();

        if (!is_null($request->get('show-moved-files'))) {
            (new GeneralHelpers($files))->dump();
        }
    }


}