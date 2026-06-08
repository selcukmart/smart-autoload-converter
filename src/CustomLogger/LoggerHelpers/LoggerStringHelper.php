<?php
/**
 * @author Selcuk Mart
 * 20.07.2022
 * 13:48
 */

namespace App\CustomLogger\LoggerHelpers;



class LoggerStringHelper
{

    public function __construct(private readonly mixed $value)
    {
    }


    /**
     * @throws \JsonException
     */
    public function toString(): string
    {
        if (is_scalar($this->value)) {
            return $this->setPre((string)$this->value);
        }
        if (is_object($this->value) && method_exists($this->value, '__toString')) {
            return $this->setPre((string)$this->value);
        }
        if (is_array($this->value)) {
            return json_encode($this->value, JSON_THROW_ON_ERROR);
        }
        return gettype($this->value);
    }

    private function setPre(string $str)
    {
        return '<pre>' . $str . '</pre>';
    }

}