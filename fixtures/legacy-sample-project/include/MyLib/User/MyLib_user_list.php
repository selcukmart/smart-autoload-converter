<?php
/**
 * Legacy class with "list" suffix (generic class name).
 * Expected conversion: MyLib\User\UserList
 */
class MyLib_user_list
{
    private $users = [];

    public function add(MyLib_user_construct $user)
    {
        $this->users[] = $user;
    }

    public function getAll()
    {
        return $this->users;
    }

    public function count()
    {
        return count($this->users);
    }
}
