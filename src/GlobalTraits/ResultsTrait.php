<?php
/**
 * @author selcukmart
 * 31.10.2021
 * 11:23
 */

namespace App\GlobalTraits;

trait ResultsTrait
{

    private bool $result = false;

    /**
     * @return bool
     */
    public function isResult(): bool
    {
        return $this->result;
    }

    /**
     * @param bool $result
     */
    public function setResult(bool $result): void
    {
        $this->result = $result;
    }

}