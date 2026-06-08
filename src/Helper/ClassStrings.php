<?php
/**
 * @author selcukmart
 * 10.03.2022
 * 10:05
 */

namespace App\Helper;

class ClassStrings
{
    private static array $instance = [];
    private string
        $method_name = '',
        $classname = '';

    public function __construct(private string $str)
    {
    }

    public static function getInstance($str): self
    {
        if (!isset(self::$instance[$str])) {
            self::$instance[$str] = new self($str);
        }
        return self::$instance[$str];
    }

    public function prepareMethodNameFromString(): string
    {
        if (!empty($this->method_name)) {
            return $this->method_name;
        }
        $this->str = str_replace('-', '_', $this->str);
        $exploded = explode('_', mb_strtolower($this->str));
        $this->method_name = mb_strtolower($exploded[0]);
        unset($exploded[0]);
        foreach ($exploded as $value) {
            $this->method_name .= ucfirst($value);
        }
        return $this->method_name;
    }

    public function prepareClassName(): string
    {
        if (!empty($this->classname)) {
            return $this->classname;
        }
        $this->str = str_replace('-', '_', $this->str);
        $x = explode('_', mb_strtolower($this->str));
        $this->classname = '';
        foreach ($x as $value) {
            $this->classname .= ucfirst($value);
        }
        return $this->classname;
    }

    /**
     * @return string
     */
    public function getClassname(): string
    {
        return $this->classname;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->method_name;
    }

}