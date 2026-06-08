<?php
/**
 * @author Selcuk Mart
 * 29.07.2022
 * 15:03
 */

namespace App\Converter\Directions;

use App\Converter\AutoloadConverterBuilder;

abstract class AbstractDirections implements DirectionsInterface
{
    public function __construct(protected AutoloadConverterBuilder $autoloadConverterBuilder)
    {
    }
}