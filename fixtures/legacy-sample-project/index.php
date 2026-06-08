<?php
/**
 * Entry point with multiple requires.
 */
require_once 'config.php';
require_once 'scripts/helpers.php';
require_once 'include/MyLib/User/MyLib_user_construct.php';
require_once 'include/MyLib/Market/MyLib_market_construct.php';
require_once 'include/Vendor/Vendor_db_adapter.php';

$user = new MyLib_user_construct('Admin', 'admin@example.com');
$market = new MyLib_market_construct(1, 'Main Market');

echo APP_NAME . ': ' . $user->getName() . ' @ ' . $market->getName();
