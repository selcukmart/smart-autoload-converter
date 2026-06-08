<?php
/**
 * @author Selcuk Mart
 * 18.07.2022
 * 15:08
 */

namespace App\Converter\Directions;

use App\Helper\ServiceHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

class ZipWholeFolder extends AbstractDirections
{


    private array $filename = [];
    /**
     * @var string
     * @author Selcuk Mart
     * 1.08.2022
     * 14:47
     */
    private $filename_slug = [];
    /**
     * @var array|false
     * @author Selcuk Mart
     * 1.08.2022
     * 15:08
     */
    private array|false $current_files;

    private array|false $folder_name;

    public function createZip(): void
    {
        $this->current_files = scandir($this->getVarDir(), SCANDIR_SORT_NONE);
        if (!$this->autoloadConverterBuilder->getSettings('backup')) {
            return;
        }

        foreach ($this->autoloadConverterBuilder->getSettings('class_change_dirs') as $dir) {
            if ($this->isContain($dir)) {
                continue;
            }
            $zip = new ZipArchive();
            $zip->open($this->getFilename($dir), ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $rootPath = realpath($dir);

            // Initialize archive object
            // Create recursive directory iterator
            /** @var SplFileInfo[] $files */
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rootPath),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                // Skip directories (they would be added automatically)
                if (!$file->isDir()) {
                    // Get real and relative path for current file
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($rootPath) + 1);

                    // Add current file to archive
                    $zip->addFile($filePath, $relativePath);
                }
            }
            // Zip archive will be created only after closing object
            $zip->close();
        }

    }


    public function getFilename(string $dir): string
    {
        if (empty($this->filename[$dir])) {
            $this->filename[$dir] = $this->getVarDir() . DIRECTORY_SEPARATOR . $this->getFilenameSlug($dir) . '_class_list.zip';
        }
        return $this->filename[$dir];
    }

    /**
     * @return string
     * @author Selcuk Mart
     * 18.07.2022
     * 14:00
     */
    public function getFilenameSlug($dir): string
    {
        if (empty($this->filename_slug[$dir])) {
            $this->filename_slug[$dir] = $this->getFolderName($dir) . '-' . date('Y_m_d_H_i_s');
        }
        return $this->filename_slug[$dir];
    }

    /**
     * @param string $project_dir
     * @return string
     * @author Selcuk Mart
     * 1.08.2022
     * 15:06
     */
    private function getVarDir(): string
    {
        return ServiceHelper::getKernel()->getProjectDir() . DIRECTORY_SEPARATOR . 'var';
    }

    /**
     * @param $ex
     * @return mixed
     * @author Selcuk Mart
     * 1.08.2022
     * 15:08
     */
    private function getFolderName($dir): mixed
    {
        if (!isset($this->folder_name[$dir])) {
            $ex = explode('/', $dir);
            $this->folder_name[$dir] = $ex[count($ex) - 1];
        }

        return $this->folder_name[$dir];
    }

    /**
     * @param mixed $dir
     * @return bool
     * @author Selcuk Mart
     * 1.08.2022
     * 15:15
     */
    private function isContain(mixed $dir): bool
    {
        $folder_name = $this->getFolderName($dir);
        $contain = false;
        foreach ($this->current_files as $current_file) {
            if (str_contains($current_file, $folder_name)) {
                $contain = true;
                break;
            }
        }
        return $contain;
    }
}