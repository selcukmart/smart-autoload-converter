<?php
/**
 * SAME NAME TEST: This class name "viewController" exists in two directories.
 * This is the UI version. SameNameResolver must distinguish from admin version.
 */
class viewController extends MyLib_UI_Abstract
{
    public function render()
    {
        return '<div class="ui-view">UI View</div>';
    }
}
