<?php

namespace App\CustomLogger;

use Psr\Log\LogLevel;

/**
 * Describes log levels.
 */
class CustomLogLevel extends LogLevel
{
    const CUSTOM = 'custom';
}
