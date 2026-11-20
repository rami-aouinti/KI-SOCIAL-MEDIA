<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContentApprovalDecision;
use App\Events\ContentHandedOff;
use App\Enums\ContentVersionStatus;
use App\Models\ContentHandoffRecord;
use App\Models\ContentVersion;
use App\Repositories\Contracts\ContentApprovalRepositoryInterface;
use App\Repositories\Contracts\ContentHandoffRepositoryInterface;
use App\Repositories\Contracts\ContentVersionRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class SimulatedContentHandoffService
{
    public function __construct(
        private readonly ContentVersionReadinessValidator $readinessValidator,
        private readonly ContentVersionRepositoryInterface $versions,
        private readonly ContentApprovalRepositoryInterface $approvals,
        private readonly ContentHandoffRepositoryInterface $handoffs,
    ) {
    }

    public function handoff(ContentVersion $version): ContentHandoffRecord
    {
        return DB::transaction(function () use ($version): ContentHandoffRecord {
            $locked = $this->versions->lockForWorkflow((int) $version->id, true);

            // Return the original result on retries. Never create a second handoff.
            $existing = $this->handoffs->findForVersion((int) $locked->id);

            if ($existing) {
                return $existing;
            }

            if ($locked->status !== ContentVersionStatus::APPROVED) {
                throw new LogicException('Only approved content versions can be handed off.');
            }

            $approval = $this->approvals->findLatestForVersionAndHash(
                (int) $locked->id,
                ContentApprovalDecision::APPROVED,
                $locked->content_hash,
            );

            if (! $approval) {
                throw ValidationException::withMessages([
                    'approval' => 'A valid approval for this exact content version is required.',
                ]);
            }

            $this->readinessValidator->assertReady($locked);

            $snapshot = $locked->snapshot ?? [];
            $idea = $locked->briefing?->contentIdea;

            $payload = [
                'mode' => 'simulated',
                'destination' => 'simulated-publishing-interface',
                'content_version_id' => $locked->id,
                'version_number' => $locked->version_number,
                'content_hash' => $locked->content_hash,
                'hook' => $snapshot['hook'],
                'body' => $snapshot['body'],
                'channel_variants' => $snapshot['channel_variants'],
                'media_reference' => $locked->media_reference,
                'channels' => $locked->channels,
                'scheduled_at' => $locked->scheduled_at->toDateString(),
                'responsible_person' => $idea->responsible_person,
            ];

            $record = $this->handoffs->create([
                'content_version_id' => $locked->id,
                'content_version_approval_id' => $approval->id,
                'idempotency_key' => sprintf('content-version:%d:simulated-handoff:v1', $locked->id),
                'payload' => $payload,
                'status' => 'handed_off',
                'handed_off_at' => Carbon::now(),
            ]);

            $locked->status = ContentVersionStatus::HANDED_OFF;
            $locked->save();
            ContentHandedOff::dispatch((int) $locked->id);

            return $record;
        });
    }
}
