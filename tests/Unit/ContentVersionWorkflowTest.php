<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Content\Entities\ContentVersionWorkflow;
use App\Domain\Shared\Enums\ContentVersionStatus;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContentVersionWorkflowTest extends TestCase
{
    public function test_a_draft_can_be_submitted_for_review(): void
    {
        $workflow = ContentVersionWorkflow::fromStatus(ContentVersionStatus::DRAFT);

        self::assertSame(ContentVersionStatus::IN_REVIEW, $workflow->submitForReview()->status());
        self::assertSame(ContentVersionStatus::DRAFT, $workflow->status());
    }

    public function test_a_version_in_review_can_be_approved_or_rejected(): void
    {
        $workflow = ContentVersionWorkflow::fromStatus(ContentVersionStatus::IN_REVIEW);

        self::assertSame(ContentVersionStatus::APPROVED, $workflow->approve()->status());
        self::assertSame(ContentVersionStatus::REJECTED, $workflow->reject()->status());
    }

    public function test_only_an_approved_version_can_be_handed_off(): void
    {
        $workflow = ContentVersionWorkflow::fromStatus(ContentVersionStatus::APPROVED);

        self::assertSame(ContentVersionStatus::HANDED_OFF, $workflow->handOff()->status());
    }

    #[DataProvider('invalidTransitions')]
    public function test_it_rejects_invalid_transitions(
        ContentVersionStatus $initial,
        string $transition,
    ): void {
        $this->expectException(DomainException::class);

        ContentVersionWorkflow::fromStatus($initial)->{$transition}();
    }

    /**
     * @return array<string, array{ContentVersionStatus, string}>
     */
    public static function invalidTransitions(): array
    {
        return [
            'cannot approve a draft' => [ContentVersionStatus::DRAFT, 'approve'],
            'cannot reject a draft' => [ContentVersionStatus::DRAFT, 'reject'],
            'cannot hand off a draft' => [ContentVersionStatus::DRAFT, 'handOff'],
            'cannot submit an approved version again' => [ContentVersionStatus::APPROVED, 'submitForReview'],
            'cannot approve a rejected version' => [ContentVersionStatus::REJECTED, 'approve'],
            'cannot hand off a version in review' => [ContentVersionStatus::IN_REVIEW, 'handOff'],
            'cannot hand off twice' => [ContentVersionStatus::HANDED_OFF, 'handOff'],
        ];
    }
}
