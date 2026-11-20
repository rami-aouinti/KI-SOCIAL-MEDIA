<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentHandoffRecord extends Model
{
    protected $fillable = [
        'content_version_id',
        'content_version_approval_id',
        'idempotency_key',
        'payload',
        'status',
        'handed_off_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'handed_off_at' => 'datetime',
        ];
    }

    public function contentVersion(): BelongsTo
    {
        return $this->belongsTo(ContentVersion::class);
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(ContentVersionApproval::class, 'content_version_approval_id');
    }
}
