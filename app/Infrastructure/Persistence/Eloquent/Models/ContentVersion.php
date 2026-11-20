<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Shared\Enums\ContentVersionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

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

    protected static function booted(): void
    {
        static::updating(static function (ContentVersion $version): void {
            $immutableFields = [
                'content_briefing_id',
                'version_number',
                'snapshot',
                'media_reference',
                'channels',
                'scheduled_at',
                'content_hash',
            ];

            if ($version->isDirty($immutableFields)) {
                throw new LogicException(
                    'A content version snapshot is immutable. Create a new version instead.'
                );
            }
        });
    }

    public function briefing(): BelongsTo
    {
        return $this->belongsTo(ContentBriefing::class, 'content_briefing_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ContentVersionApproval::class);
    }

    public function handoffRecords(): HasMany
    {
        return $this->hasMany(ContentHandoffRecord::class);
    }
}
