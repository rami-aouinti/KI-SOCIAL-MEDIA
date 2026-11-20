<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Shared\Enums\ContentApprovalDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentVersionApproval extends Model
{
    protected $fillable = [
        'content_version_id',
        'reviewer',
        'decision',
        'content_hash',
        'comment',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'decision' => ContentApprovalDecision::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function contentVersion(): BelongsTo
    {
        return $this->belongsTo(ContentVersion::class);
    }
}
