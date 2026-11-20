<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Content\Entities\ContentBriefing;
use App\Domain\Content\Entities\ContentIdea;
use App\Domain\Planning\Entities\WeeklyPlan;
use App\Domain\Shared\Enums\ContentBriefingStatus;
use App\Domain\Shared\Enums\ContentIdeaPriority;
use App\Domain\Shared\Enums\ContentIdeaStatus;
use App\Domain\Shared\Enums\WeeklyPlanStatus;
use DomainException;
use PHPUnit\Framework\TestCase;

final class DomainEntitiesTest extends TestCase
{
    public function test_weekly_plan_requires_three_priority_ideas_and_two_reserves(): void
    {
        $plan = new WeeklyPlan(1, '2026-11-16');

        self::assertSame(WeeklyPlanStatus::READY, $plan->markReady(3, 2)->status);
        $this->expectException(DomainException::class);
        $plan->markReady(2, 3);
    }

    public function test_weekly_plan_cannot_be_completed_before_it_is_ready(): void
    {
        $this->expectException(DomainException::class);
        (new WeeklyPlan(1, '2026-11-16'))->complete();
    }

    public function test_content_idea_has_an_explicit_production_lifecycle(): void
    {
        $idea = new ContentIdea(1, 'Winter campaign', ContentIdeaPriority::PRIORITY);
        $producing = $idea->startProduction();

        self::assertSame(ContentIdeaStatus::IN_PRODUCTION, $producing->status);
        self::assertSame(ContentIdeaStatus::PLANNED, $idea->status);
        self::assertSame(ContentIdeaStatus::COMPLETED, $producing->complete()->status);
    }

    public function test_content_briefing_requires_complete_copy_before_ready(): void
    {
        $briefing = new ContentBriefing(1, 1);

        $this->expectException(DomainException::class);
        $briefing->markReady('Hook', '', 'Book now');
    }

    public function test_content_briefing_can_be_marked_ready_with_required_copy(): void
    {
        $briefing = new ContentBriefing(1, 1);

        self::assertSame(
            ContentBriefingStatus::READY,
            $briefing->markReady('Hook', 'Body', 'Book now')->status,
        );
    }
}
