<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentVersionStatus: string
{
    case DRAFT = 'draft';
    case IN_REVIEW = 'in_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case HANDED_OFF = 'handed_off';
}
