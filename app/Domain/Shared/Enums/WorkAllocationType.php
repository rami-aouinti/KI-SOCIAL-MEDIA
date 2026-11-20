<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum WorkAllocationType: string
{
    case PRODUCTION = 'production';
    case VARIANTS = 'variants';
    case COORDINATION = 'coordination';
    case APPROVAL = 'approval';
}
