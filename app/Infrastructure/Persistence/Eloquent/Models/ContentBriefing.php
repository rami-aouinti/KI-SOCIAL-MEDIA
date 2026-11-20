<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Enums\ContentBriefingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentBriefing extends Model
{
    protected $fillable = [
        'content_idea_id',
        'hook',
        'body',
        'filming_plan',
        'on_screen_text',
        'cover_idea',
        'cta_text',
        'appointment_url',
        'status',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'on_screen_text' => 'array',
            'status' => ContentBriefingStatus::class,
        ];
    }

    public function contentIdea(): BelongsTo
    {
        return $this->belongsTo(ContentIdea::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ContentVersion::class);
    }
}
