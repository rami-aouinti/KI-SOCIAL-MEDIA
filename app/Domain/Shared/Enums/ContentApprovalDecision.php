<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum ContentApprovalDecision: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
