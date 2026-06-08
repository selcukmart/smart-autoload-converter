<?php
/**
 * @author Selcuk Mart
 * 11.08.2022
 * 10:07
 */

namespace App\Converter\Directions\HelperTraits;

use Exception;
use RuntimeException;
use function App\Helper\c;

trait DirFileOperationsTrait
{
    private function removeUnnecessaryFileAndFolder($dir, $class_information): void
    {
        //if ($class_information['to_be_moved']) {
        $removal_file_path = str_replace('//', '/', $dir . '/' . $class_information['file_path']);
        if (is_file($removal_file_path)) {
            unlink($removal_file_path);
        }

        $removal_file_dir = dirname($removal_file_path);
        if (is_dir($removal_file_dir)) {
            $file_list = scandir($removal_file_dir, SCANDIR_SORT_NONE);
            foreach ($file_list as $item) {
                if ($item === '.' || $item === '..') {
                    unset($item);
                }
            }
            if (count($file_list) === 0) {
                rmdir($removal_file_dir);
            }
        }
        //}
    }

    private function moveFile($read_dir, $base_dir, $class_information, $to_be_moved_dir): void
    {
        $concurrentDirectory = dirname($base_dir . $to_be_moved_dir);
        $this->createDir($concurrentDirectory);

        $content = $this->readContentFromOldClassFile($read_dir . $class_information['file_path']);

        $this->removeUnnecessaryFileAndFolder($base_dir, $class_information);
        $this->writeContentToNewClassFile($content, $base_dir, $class_information['new_file_path']);

    }

    private function writeContentToNewClassFile(bool|string $content, $base_dir, $new_file_path): void
    {
        $write_path = $base_dir . $new_file_path;
        $concurrentDirectory = dirname($write_path);
        $this->createDir($concurrentDirectory);
        try {
            if (is_file($write_path)) {
                unlink($write_path);
            }
            $open_file = fopen($write_path, 'cb');
            fwrite($open_file, $content);
            fclose($open_file);
            chmod($write_path, 0644);
        } catch (Exception $e) {
            c($e->getMessage());
        }

    }

    private function readContentFromOldClassFile(string $file_path): string|false
    {
        return file_get_contents($file_path);
    }

    /**
     * @param string $concurrentDirectory
     * @author Selcuk Mart
     * 15.08.2022
     * 16:00
     */
    private function createDir(string $concurrentDirectory): void
    {
        if (!is_dir($concurrentDirectory)
            && !is_file($concurrentDirectory)
            && !mkdir($concurrentDirectory, 0755, true)
            && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }
}