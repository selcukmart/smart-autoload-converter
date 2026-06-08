<?php
/**
 * SAME NAME TEST: Admin viewController (different from UI/viewController).
 */
require_once '../../include/MyLib/UI/viewController.php';

class viewController
{
    public function handleAdmin()
    {
        $uiView = new viewController();
        return 'admin:' . $uiView->render();
    }
}
