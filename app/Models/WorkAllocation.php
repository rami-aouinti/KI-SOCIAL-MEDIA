<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorkAllocationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkAllocation extends Model
{
    protected $fillable = [
        'content_idea_id',
        'person_key',
        'work_type',
        'estimated_minutes',
    ];

    protected function casts(): array
    {
        return [
            'work_type' => WorkAllocationType::class,
            'estimated_minutes' => 'integer',
        ];
    }

    public function contentIdea(): BelongsTo
    {
        return $this->belongsTo(ContentIdea::class);
    }
}
