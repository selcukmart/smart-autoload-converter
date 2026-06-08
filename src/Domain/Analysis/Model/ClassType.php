<?php

declare(strict_types=1);

namespace SmartAutoloadConverter\Domain\Analysis\Model;

enum ClassType: string
{
    case Class_ = 'class';
    case AbstractClass = 'abstract_class';
    case Interface_ = 'interface';
    case Trait_ = 'trait';
    case Enum_ = 'enum';
}
