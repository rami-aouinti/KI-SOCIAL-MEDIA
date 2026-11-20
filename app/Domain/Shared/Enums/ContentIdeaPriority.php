<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum ContentIdeaPriority: string
{
    case PRIORITY = 'priority';
    case RESERVE = 'reserve';
}
