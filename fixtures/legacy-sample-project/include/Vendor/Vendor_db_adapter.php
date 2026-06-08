<?php
/**
 * Expected: Vendor\Db\Adapter
 */
class Vendor_db_adapter extends Vendor_db_Abstract
{
    public function connect()
    {
        $this->connection = 'mysql:connected';
        return $this;
    }

    public function query($sql)
    {
        return [];
    }
}
