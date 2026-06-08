<?php
/**
 * @author selcukmart
 * 31.10.2021
 * 11:22
 */

namespace App\GlobalTraits;

trait ErrorMessagesWithResultTrait
{
    use ResultsTrait;

    private string
        $error_message = '',
        $error = '';

    /**
     * @param string $error_message
     */
    public function setErrorMessage(string $error_message): void
    {
        $this->result = false;
        $this->error_message .= $error_message . PHP_EOL;
    }

    /**
     * @param string $error
     */
    public function setError(string $error): void
    {
        $this->error = $error;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->error_message;
    }
}