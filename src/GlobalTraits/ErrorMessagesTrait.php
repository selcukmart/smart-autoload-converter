<?php
/**
 * @author selcukmart
 * 31.10.2021
 * 11:22
 */

namespace App\GlobalTraits;

trait ErrorMessagesTrait
{
    private string
        $error_message = '';

    /**
     * @param string $error_message
     */
    public function setErrorMessage(string $error_message): void
    {
        $this->error_message .= $error_message . PHP_EOL;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->error_message;
    }
}