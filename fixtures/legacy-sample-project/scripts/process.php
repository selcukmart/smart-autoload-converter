<?php
/**
 * Script with instanceof, static call, catch.
 * Tests all major replacement patterns.
 */
include '../include/MyLib/User/MyLib_user_construct.php';
include '../include/Vendor/Vendor_db_adapter.php';

$db = new Vendor_db_adapter();
$db->connect();

$user = MyLib_user_construct::create('Test', 'test@test.com');

if ($user instanceof MyLib_user_construct) {
    echo 'Valid user: ' . $user->getName();
}

try {
    $db->query('SELECT * FROM users');
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
