<?php
/**
 * @author Selcuk Mart
 * 25.08.2022
 * 15:49
 */

namespace App\Converter\Directions;

use App\Converter\AutoloadConverterBuilder;

class ChangeFileContentsAfterCreating
{
    public function __construct()
    {
    }

    public function execute(): void
    {
        $change_file_contents = AutoloadConverterBuilder::getInstance()->getChangeFileContents();
        foreach ($change_file_contents as  $change_file_content) {
            $file_path = AutoloadConverterBuilder::getInstance()->getToTempFolder() . $change_file_content['file_path'];
            file_put_contents($file_path, $change_file_content['content']);
        }
    }
}