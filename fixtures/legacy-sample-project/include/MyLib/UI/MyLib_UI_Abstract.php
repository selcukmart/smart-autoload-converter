<?php
/**
 * Reserved word test: "Abstract" must become "Abstracts" in namespace.
 * Expected conversion: MyLib\UI\Abstracts\UIAbstract or similar
 */
abstract class MyLib_UI_Abstract
{
    abstract public function render();

    public function getType()
    {
        return 'ui_element';
    }
}
