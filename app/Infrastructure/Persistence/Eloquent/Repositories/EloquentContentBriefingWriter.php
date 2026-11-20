<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Contracts\ContentBriefingWriterInterface;
use App\Application\DTO\BriefingIdeaData;
use App\Application\DTO\GeneratedBriefingData;
use App\Domain\Content\Records\ContentBriefingRecord;
use App\Infrastructure\Persistence\Eloquent\Models\ContentBriefing;
use App\Infrastructure\Persistence\Eloquent\Models\ContentIdea;
use Illuminate\Validation\ValidationException;

final class EloquentContentBriefingWriter implements ContentBriefingWriterInterface
{
    public function findIdea(int $ideaId): BriefingIdeaData
    {
        $idea = ContentIdea::query()->with('weeklyPlan')->findOrFail($ideaId);

        return new BriefingIdeaData(
            id: (int) $idea->getKey(),
            title: $idea->title,
            topic: $idea->topic,
            targetAudience: $idea->target_audience,
            channels: $idea->channels ?? [],
            plannedAt: $idea->planned_at->format('Y-m-d'),
            seasonalOccasion: $idea->weeklyPlan?->seasonal_occasion,
            tone: $idea->weeklyPlan?->input_data['tone'] ?? 'friendly and personal',
        );
    }

    public function create(int $ideaId, GeneratedBriefingData $data): ContentBriefingRecord
    {
        if (ContentBriefing::query()->where('content_idea_id', $ideaId)->exists()) {
            throw ValidationException::withMessages([
                'briefing' => 'A briefing already exists. Create a new content version instead of overwriting it.',
            ]);
        }

        $briefing = ContentBriefing::query()->create([
            'content_idea_id' => $ideaId,
            'hook' => $data->hook,
            'body' => $data->body,
            'filming_plan' => $data->filmingPlan,
            'on_screen_text' => $data->onScreenText,
            'cover_idea' => $data->coverIdea,
            'cta_text' => $data->ctaText,
            'appointment_url' => $data->appointmentUrl,
            'status' => 'ready',
        ]);

        return new ContentBriefingRecord((int) $briefing->getKey());
    }
}
