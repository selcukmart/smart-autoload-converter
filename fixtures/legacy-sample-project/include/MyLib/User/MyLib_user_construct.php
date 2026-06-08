<?php
/**
 * Legacy class: underscore-separated naming, no namespace.
 * Expected conversion: MyLib\User\User (construct suffix dropped)
 */
class MyLib_user_construct
{
    private $name;
    private $email;

    public function __construct($name, $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    public static function create($name, $email)
    {
        return new MyLib_user_construct($name, $email);
    }

    public function getName()
    {
        return $this->name;
    }
}
