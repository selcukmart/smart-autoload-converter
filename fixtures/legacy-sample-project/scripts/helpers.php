<?php
/**
 * NON-CLASS FILE: Contains only functions, no class definition.
 * Include statements to this file should NOT be removed (it's not autoloadable).
 */

function formatDate($timestamp)
{
    return date('Y-m-d H:i', $timestamp);
}

function sanitizeInput($input)
{
    return htmlspecialchars(strip_tags($input));
}

function generateSlug($text)
{
    return strtolower(preg_replace('/[^a-z0-9]+/i', '-', $text));
}
