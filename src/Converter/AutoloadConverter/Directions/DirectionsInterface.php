<?php
/**
 * @author Selcuk Mart
 * 29.07.2022
 * 15:01
 */

namespace App\Converter\Directions;

use App\Converter\AutoloadConverterBuilder;

interface DirectionsInterface
{
    public function __construct(AutoloadConverterBuilder $autoloadConverterBuilder);
}