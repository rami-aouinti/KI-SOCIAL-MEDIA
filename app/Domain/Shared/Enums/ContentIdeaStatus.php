<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum ContentIdeaStatus: string
{
    case PLANNED = 'planned';
    case IN_PRODUCTION = 'in_production';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
