<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContentApprovalDecision;
use App\Enums\ContentVersionStatus;
use App\Models\ContentVersion;
use App\Models\ContentVersionApproval;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class ContentApprovalService
{
    public function submitForReview(ContentVersion $version): ContentVersion
    {
        return DB::transaction(function () use ($version): ContentVersion {
            $locked = ContentVersion::query()->lockForUpdate()->findOrFail($version->id);

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
            $locked = ContentVersion::query()
                ->with('briefing.contentIdea.workAllocations')
                ->lockForUpdate()
                ->findOrFail($version->id);

            if ($locked->status !== ContentVersionStatus::IN_REVIEW) {
                throw new LogicException('Only versions in review can be approved.');
            }

            $this->assertReadyForApproval($locked);

            $reviewer = trim($reviewer);
            if ($reviewer === '') {
                throw ValidationException::withMessages([
                    'reviewer' => 'A reviewer is required.',
                ]);
            }

            ContentVersionApproval::create([
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
            $locked = ContentVersion::query()->lockForUpdate()->findOrFail($version->id);

            if ($locked->status !== ContentVersionStatus::IN_REVIEW) {
                throw new LogicException('Only versions in review can be rejected.');
            }

            $reviewer = trim($reviewer);
            if ($reviewer === '') {
                throw ValidationException::withMessages([
                    'reviewer' => 'A reviewer is required.',
                ]);
            }

            ContentVersionApproval::create([
                'content_version_id' => $locked->id,
                'reviewer' => $reviewer,
                'decision' => ContentApprovalDecision::REJECTED,
                'content_hash' => $locked->content_hash,
                'comment' => $comment,
                'reviewed_at' => Carbon::now(),
            ]);

            // A rejected version remains immutable. Any edits require creating a new version.
            $locked->status = ContentVersionStatus::DRAFT;
            $locked->save();

            return $locked->refresh();
        });
    }

    private function assertReadyForApproval(ContentVersion $version): void
    {
        $errors = [];
        $snapshot = $version->snapshot ?? [];
        $idea = $version->briefing?->contentIdea;

        if (trim((string) ($snapshot['hook'] ?? '')) === '') {
            $errors['hook'] = 'A hook is required.';
        }

        if (trim((string) ($snapshot['body'] ?? '')) === '') {
            $errors['body'] = 'A text body is required.';
        }

        if (blank($version->media_reference)) {
            $errors['media_reference'] = 'A media reference is required before approval.';
        }

        if (empty($version->channels)) {
            $errors['channels'] = 'At least one channel is required.';
        }

        if (! $version->scheduled_at) {
            $errors['scheduled_at'] = 'A publication date is required.';
        }

        if (! $idea || blank($idea->responsible_person)) {
            $errors['responsible_person'] = 'A responsible person must be assigned.';
        }

        if (! $idea || $idea->workAllocations->isEmpty()) {
            $errors['work_allocations'] = 'At least one work allocation is required.';
        }

        $variants = $snapshot['channel_variants'] ?? [];
        foreach ($version->channels ?? [] as $channel) {
            if (blank($variants[$channel]['text'] ?? null)) {
                $errors['channel_variants'] = "A text variant is required for channel: {$channel}.";
                break;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
