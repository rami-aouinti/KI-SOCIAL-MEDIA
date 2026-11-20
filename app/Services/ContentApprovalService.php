<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContentApprovalDecision;
use App\Enums\ContentVersionStatus;
use App\Models\ContentVersion;
use App\Repositories\Contracts\ContentApprovalRepositoryInterface;
use App\Repositories\Contracts\ContentVersionRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class ContentApprovalService
{
    public function __construct(
        private readonly ContentVersionReadinessValidator $readinessValidator,
        private readonly ContentVersionRepositoryInterface $versions,
        private readonly ContentApprovalRepositoryInterface $approvals,
    ) {
    }

    public function submitForReview(ContentVersion $version): ContentVersion
    {
        return DB::transaction(function () use ($version): ContentVersion {
            $locked = $this->versions->lockForWorkflow((int) $version->id);

            if ($locked->status !== ContentVersionStatus::DRAFT) {
                throw new LogicException('Only draft versions can be submitted for review.');
            }

            $locked->status = ContentVersionStatus::IN_REVIEW;
            $locked->save();

            return $locked->refresh();
        });
    }

    public function approve(
        ContentVersion $version,
        string $reviewer,
        ?string $comment = null,
    ): ContentVersion {
        return DB::transaction(function () use ($version, $reviewer, $comment): ContentVersion {
            $locked = $this->versions->lockForWorkflow((int) $version->id, true);

            if ($locked->status !== ContentVersionStatus::IN_REVIEW) {
                throw new LogicException('Only versions in review can be approved.');
            }

            $this->readinessValidator->assertReady($locked);

            $reviewer = trim($reviewer);
            if ($reviewer === '') {
                throw ValidationException::withMessages([
                    'reviewer' => 'A reviewer is required.',
                ]);
            }

            $this->approvals->create([
                'content_version_id' => $locked->id,
                'reviewer' => $reviewer,
                'decision' => ContentApprovalDecision::APPROVED,
                'content_hash' => $locked->content_hash,
                'comment' => $comment,
                'reviewed_at' => Carbon::now(),
            ]);

            $locked->status = ContentVersionStatus::APPROVED;
            $locked->save();

            return $locked->refresh();
        });
    }

    public function reject(
        ContentVersion $version,
        string $reviewer,
        ?string $comment = null,
    ): ContentVersion {
        return DB::transaction(function () use ($version, $reviewer, $comment): ContentVersion {
            $locked = $this->versions->lockForWorkflow((int) $version->id);

            if ($locked->status !== ContentVersionStatus::IN_REVIEW) {
                throw new LogicException('Only versions in review can be rejected.');
            }

            $reviewer = trim($reviewer);
            if ($reviewer === '') {
                throw ValidationException::withMessages([
                    'reviewer' => 'A reviewer is required.',
                ]);
            }

            $this->approvals->create([
                'content_version_id' => $locked->id,
                'reviewer' => $reviewer,
                'decision' => ContentApprovalDecision::REJECTED,
                'content_hash' => $locked->content_hash,
                'comment' => $comment,
                'reviewed_at' => Carbon::now(),
            ]);

            // A rejected version remains immutable and cannot be resubmitted.
            // Any edits require creating a new version.
            $locked->status = ContentVersionStatus::REJECTED;
            $locked->save();

            return $locked->refresh();
        });
    }
}
