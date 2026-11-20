<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentVersionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentVersion extends Model
{
    protected $fillable = [
        'content_briefing_id',
        'version_number',
        'snapshot',
        'media_reference',
        'channels',
        'scheduled_at',
        'content_hash',
        'status',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'snapshot' => 'array',
            'channels' => 'array',
            'scheduled_at' => 'date',
            'status' => ContentVersionStatus::class,
        ];
    }

    public function briefing(): BelongsTo
    {
        return $this->belongsTo(ContentBriefing::class, 'content_briefing_id');
    }
}
