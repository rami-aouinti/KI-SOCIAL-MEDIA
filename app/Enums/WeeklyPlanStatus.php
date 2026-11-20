<?php

declare(strict_types=1);

namespace App\Enums;

enum WeeklyPlanStatus: string
{
    case DRAFT = 'draft';
    case READY = 'ready';
    case COMPLETED = 'completed';
}
