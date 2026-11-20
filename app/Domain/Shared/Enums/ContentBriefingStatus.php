<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum ContentBriefingStatus: string
{
    case DRAFT = 'draft';
    case READY = 'ready';
}
