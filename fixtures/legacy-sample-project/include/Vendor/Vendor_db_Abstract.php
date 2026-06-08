<?php
/**
 * Vendor class with "Abstract" reserved word.
 * Expected: Vendor\Db\Abstracts\DbAbstract or similar
 */
abstract class Vendor_db_Abstract
{
    protected $connection;

    abstract public function connect();

    public function getConnection()
    {
        return $this->connection;
    }
}
