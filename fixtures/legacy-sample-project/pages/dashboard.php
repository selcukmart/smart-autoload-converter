<?php
/**
 * Dashboard page: uses 5 include_once statements and multiple class references.
 * Tests: include removal, new X(), static calls, function params.
 */
include_once 'include/MyLib/User/MyLib_user_construct.php';
include_once 'include/MyLib/User/MyLib_user_list.php';
include_once 'include/MyLib/Market/MyLib_market_construct.php';
include_once 'include/MyLib/Market/MyLib_market_view.php';
include_once 'include/Vendor/Vendor_db_adapter.php';

// new instance
$user = new MyLib_user_construct('John', 'john@example.com');
$user2 = MyLib_user_construct::create('Jane', 'jane@example.com');

// typed parameter
function showUser(MyLib_user_construct $user) {
    echo $user->getName();
}

// instanceof check
if ($user instanceof MyLib_user_construct) {
    $list = new MyLib_user_list();
    $list->add($user);
    $list->add($user2);
}

$market = new MyLib_market_construct(1, 'Berlin Market');
$view = new MyLib_market_view(2, 'Hamburg Market');
echo $view->render();
