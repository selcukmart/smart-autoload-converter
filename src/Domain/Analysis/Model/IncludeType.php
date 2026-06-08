<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Analysis\Model;

enum IncludeType: string
{
    case Include = 'include';
    case IncludeOnce = 'include_once';
    case Require = 'require';
    case RequireOnce = 'require_once';
}
