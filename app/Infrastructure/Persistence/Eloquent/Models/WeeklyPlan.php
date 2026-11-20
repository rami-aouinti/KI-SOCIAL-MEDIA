<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Shared\Enums\WeeklyPlanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyPlan extends Model
{
    protected $fillable = [
        'week_start',
        'seasonal_occasion',
        'input_data',
        'capacity_limits',
        'status',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'input_data' => 'array',
            'capacity_limits' => 'array',
            'status' => WeeklyPlanStatus::class,
        ];
    }

    public function inspirations(): HasMany
    {
        return $this->hasMany(InspirationReference::class);
    }

    public function contentIdeas(): HasMany
    {
        return $this->hasMany(ContentIdea::class);
    }
}
