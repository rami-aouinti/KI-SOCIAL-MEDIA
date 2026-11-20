<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Content\Entities\ContentVersionWorkflow;
use App\Domain\Shared\Enums\ContentApprovalDecision;
use App\Transport\Events\ContentApproved;
use App\Transport\Events\ContentRejected;
use App\Domain\Shared\Enums\ContentVersionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\ContentVersion;
use App\Domain\Repositories\ContentApprovalRepositoryInterface;
use App\Domain\Repositories\ContentVersionRepositoryInterface;
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

            $locked->status = ContentVersionWorkflow::fromStatus($locked->status)
                ->submitForReview()
                ->status();
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

            $nextStatus = ContentVersionWorkflow::fromStatus($locked->status)->approve()->status();

            $this->readinessValidator->assertReady($locked);
            $reviewer = $this->normalizeReviewer($reviewer);

            $this->approvals->create([
                'content_version_id' => $locked->id,
                'reviewer' => $reviewer,
                'decision' => ContentApprovalDecision::APPROVED,
                'content_hash' => $locked->content_hash,
                'comment' => $comment,
                'reviewed_at' => Carbon::now(),
            ]);

            $locked->status = $nextStatus;
            $locked->save();
            ContentApproved::dispatch((int) $locked->id);

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

            $nextStatus = ContentVersionWorkflow::fromStatus($locked->status)->reject()->status();

            $reviewer = $this->normalizeReviewer($reviewer);

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
            $locked->status = $nextStatus;
            $locked->save();
            ContentRejected::dispatch((int) $locked->id);

            return $locked->refresh();
        });
    }

    private function normalizeReviewer(string $reviewer): string
    {
        $reviewer = trim($reviewer);

        if ($reviewer === '') {
            throw ValidationException::withMessages([
                'reviewer' => 'A reviewer is required.',
            ]);
        }

        if (mb_strlen($reviewer) > 120) {
            throw ValidationException::withMessages([
                'reviewer' => 'The reviewer name may not be greater than 120 characters.',
            ]);
        }

        return $reviewer;
    }
}
