<?php
/**
 * @author Selcuk Mart
 * 18.08.2022
 * 09:45
 */

namespace App\Converter\Directions;

use App\Converter\Directions\DirectionOperations\DetermineIncludeRequireAreas;
use function App\Helper\c;

class RemoveOrChangeRequireIncludeRows
{

    public function __construct()
    {
    }

    public function execute()
    {
        (new DetermineIncludeRequireAreas())->clearClassIncludes();
    }

}