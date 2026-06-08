<?php
/**
 * @author selcukmart
 * 14.03.2022
 * 18:07
 */

namespace App\Converter\BuilderTraits;


use App\Converter\AutoloadConverterBuilder;
use App\Helper\ServiceHelper;
use JsonException;
use function App\Helper\c;

trait BuilderCommandsTraits
{

    /**
     * @throws JsonException
     */
    public function convertDirectory(): AutoloadConverterBuilder
    {
        if (!is_null(ServiceHelper::getRequest()->get('all-classes'))) {
            ServiceHelper::getRequest()->query->set('copy_from_original', null);
        }
        return $this
            ->createZip()
            ->analyzeOfFiles()
            ->removeOrChangeRequireIncludeRows()
            ->SearchAndReplaceInFiles()
            ->changeFiles()
            ->changeFileContents()
            ->removeUnnecessaryFiles()
            ->copyNecessaryFilesFolder()
            ->runComposerDumpAutoloadCommand();
    }
}