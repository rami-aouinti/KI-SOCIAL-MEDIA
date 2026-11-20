<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentIdeaPriority;
use App\Enums\ContentIdeaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentIdea extends Model
{
    protected $fillable = [
        'weekly_plan_id',
        'title',
        'topic',
        'target_audience',
        'channels',
        'format',
        'priority_type',
        'responsible_person',
        'planned_at',
        'estimated_minutes',
        'status',
    ];

    protected $attributes = [
        'status' => 'planned',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'priority_type' => ContentIdeaPriority::class,
            'planned_at' => 'date',
            'estimated_minutes' => 'integer',
            'status' => ContentIdeaStatus::class,
        ];
    }

    public function weeklyPlan(): BelongsTo
    {
        return $this->belongsTo(WeeklyPlan::class);
    }

    public function workAllocations(): HasMany
    {
        return $this->hasMany(WorkAllocation::class);
    }
}
