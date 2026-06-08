<?php
/**
 * Admin page: extends a legacy class + SAME NAME viewController.
 * Tests: extends replacement, require_once removal.
 */
require_once '../include/MyLib/User/MyLib_user_list.php';
require_once '../include/MyLib/User/MyLib_user_construct.php';

class AdminUserList extends MyLib_user_list
{
    public function getAdminUsers()
    {
        return array_filter($this->getAll(), function(MyLib_user_construct $user) {
            return $user->getName() === 'admin';
        });
    }
}
