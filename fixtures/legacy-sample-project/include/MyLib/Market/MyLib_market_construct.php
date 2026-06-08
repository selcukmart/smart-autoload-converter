<?php
/**
 * Expected conversion: MyLib\Market\Market (construct suffix dropped)
 */
class MyLib_market_construct
{
    private $id;
    private $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
}
