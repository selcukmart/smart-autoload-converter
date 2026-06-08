<?php
/**
 * Expected conversion: MyLib\Market\MarketView
 */
class MyLib_market_view extends MyLib_market_construct
{
    public function render()
    {
        return '<div>' . $this->getName() . '</div>';
    }
}
