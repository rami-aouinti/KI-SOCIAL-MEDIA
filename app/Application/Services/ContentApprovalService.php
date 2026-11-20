<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Content\Entities\ContentVersion;
use App\Domain\Content\Records\ContentVersionRecord;
use App\Domain\Events\ContentApprovedEvent;
use App\Domain\Events\ContentRejectedEvent;
use App\Domain\Events\WorkflowEventPublisherInterface;
use App\Domain\Repositories\ContentApprovalRepositoryInterface;
use App\Domain\Repositories\ContentVersionRepositoryInterface;
use App\Domain\Shared\Enums\ContentApprovalDecision;
use App\Domain\Transactions\TransactionManagerInterface;
use Illuminate\Validation\ValidationException;

final class ContentApprovalService
{
    public function __construct(
        private readonly ContentVersionReadinessValidator $readinessValidator,
        private readonly ContentVersionRepositoryInterface $versions,
        private readonly ContentApprovalRepositoryInterface $approvals,
        private readonly TransactionManagerInterface $transactions,
        private readonly WorkflowEventPublisherInterface $events,
    ) {}

    public function submitForReview(int $versionId): ContentVersionRecord
    {
        return $this->transactions->run(function () use ($versionId): ContentVersionRecord {
            $locked = $this->versions->lockForWorkflow($versionId);
            $next = ContentVersion::fromRecord($locked)->submitForReview();

            return $this->versions->updateStatus($locked->id, $next->status());
        });
    }

    public function approve(int $versionId, string $reviewer, ?string $comment = null): ContentVersionRecord
    {
        return $this->transactions->run(function () use ($versionId, $reviewer, $comment): ContentVersionRecord {
            $locked = $this->versions->lockForWorkflow($versionId, true);
            $next = ContentVersion::fromRecord($locked)->approve();
            $this->readinessValidator->assertReady($locked);
            $reviewer = $this->normalizeReviewer($reviewer);

            $this->approvals->create([
                'content_version_id' => $locked->id,
                'reviewer' => $reviewer,
                'decision' => ContentApprovalDecision::APPROVED,
                'content_hash' => $locked->contentHash,
                'comment' => $comment,
                'reviewed_at' => now(),
            ]);

            $updated = $this->versions->updateStatus($locked->id, $next->status());
            $this->events->publish(new ContentApprovedEvent($locked->id));

            return $updated;
        });
    }

    public function reject(int $versionId, string $reviewer, ?string $comment = null): ContentVersionRecord
    {
        return $this->transactions->run(function () use ($versionId, $reviewer, $comment): ContentVersionRecord {
            $locked = $this->versions->lockForWorkflow($versionId);
            $next = ContentVersion::fromRecord($locked)->reject();
            $reviewer = $this->normalizeReviewer($reviewer);

            $this->approvals->create([
                'content_version_id' => $locked->id,
                'reviewer' => $reviewer,
                'decision' => ContentApprovalDecision::REJECTED,
                'content_hash' => $locked->contentHash,
                'comment' => $comment,
                'reviewed_at' => now(),
            ]);

            $updated = $this->versions->updateStatus($locked->id, $next->status());
            $this->events->publish(new ContentRejectedEvent($locked->id));

            return $updated;
        });
    }

    private function normalizeReviewer(string $reviewer): string
    {
        $reviewer = trim($reviewer);

        if ($reviewer === '') {
            throw ValidationException::withMessages(['reviewer' => 'A reviewer is required.']);
        }

        if (mb_strlen($reviewer) > 120) {
            throw ValidationException::withMessages([
                'reviewer' => 'The reviewer name may not be greater than 120 characters.',
            ]);
        }

        return $reviewer;
    }
}
