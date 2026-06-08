<?php
/**
 * @author Selcuk Mart
 * 27.07.2022
 * 10:04
 */

namespace App\CustomLogger\LoggerHelpers;

use App\Helper\ServiceHelper;

class LoggerFileStream
{

    private static $instance;
    /**
     * @var false|resource
     * @author Selcuk Mart
     * 27.07.2022
     * 10:11
     */
    private $stream;

    public function __construct()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            $stream = new self();
            $stream->createStream();
            self::$instance = $stream;

        }
        return self::$instance;
    }

    private function createStream(): void
    {
        $project_dir = ServiceHelper::getKernel()->getProjectDir();
        $this->filename = $project_dir
            . DIRECTORY_SEPARATOR . 'var'
            . DIRECTORY_SEPARATOR . 'log'
            . DIRECTORY_SEPARATOR . $_SERVER['APP_ENV'] . '.log';
        if(!is_file($this->filename)){
            touch($this->filename);
        }
        $this->stream = fopen($this->filename, 'a+');
        //fseek($this->stream, SEEK_END);
    }

    /**
     * @return false|resource
     */
    public function getStream()
    {
        return $this->stream;
    }

}