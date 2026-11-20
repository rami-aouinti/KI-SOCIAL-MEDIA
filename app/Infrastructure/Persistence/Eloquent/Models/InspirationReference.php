<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspirationReference extends Model
{
    protected $fillable = [
        'weekly_plan_id',
        'channel',
        'source_id',
        'description',
        'metrics',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
        ];
    }

    public function weeklyPlan(): BelongsTo
    {
        return $this->belongsTo(WeeklyPlan::class);
    }
}
