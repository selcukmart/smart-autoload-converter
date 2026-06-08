<?php
/**
 * @author Selcuk Mart
 * 20.07.2022
 * 13:48
 */

namespace App\CustomLogger\LoggerHelpers;


use App\Helper\RequestHelper;
use App\Helper\ServiceHelper;

class LoggerProxy
{
    private static $log_start;

    public function __construct()
    {
    }

    public static function log($title, $message): void
    {

        if (self::logStart()) {
            ServiceHelper::getLogger()->custom($title . ': ' . (new LoggerStringHelper($message))->toString());
        }
    }

    /**
     * @return bool
     * @author Selcuk Mart
     * 26.07.2022
     * 10:18
     */
    private static function logStart(): bool
    {
        if (is_null(self::$log_start)) {
            error_reporting(-1);
            ini_set('display_errors', 'On');
            ini_set('display_startup_errors', 'On');
            ini_set('log_errors', 'On');
            self::$log_start = RequestHelper::getRequest()->query->get('logger') === 'start';
        }
        return self::$log_start;
    }

}