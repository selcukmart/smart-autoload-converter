<?php
/**
 * @author Selcuk Mart
 * 15.06.2022
 * 10:25
 */

namespace App\Helper;


use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class ServiceHelper
{
    private static array $instance = [];


    public static function getInstance($service)
    {
        return self::$instance[$service] ?? false;
    }


    public static function setInstance(mixed $instance, $service): void
    {
        self::$instance[$service] = $instance;
    }

    public static function getLogger(): LoggerInterface
    {
        return self::getInstance('logger');
    }

    public static function getRequest():Request
    {
        return self::getInstance('request');
    }

    public static function getKernel():KernelInterface
    {
        return self::getInstance('kernel');
    }
}