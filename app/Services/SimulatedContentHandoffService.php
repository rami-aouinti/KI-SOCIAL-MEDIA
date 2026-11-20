<?php

declare(strict_types=1);

namespace App\\Services;

use App\\Enums\\ContentApprovalDecision;
use App\\Enums\\ContentVersionStatus;
use App\\Models\\ContentHandoffRecord;
use App\\Models\\ContentVersion;
use App\\Models\\ContentVersionApproval;
use Illuminate\\Support\\Carbon;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\ValidationException;
use LogicException;

class SimulatedContentHandoffService
{
    public function __construct(
        private readonly ContentVersionReadinessValidator $readinessValidator,
    ) {
    }

    public function handoff(ContentVersion $version): ContentHandoffRecord
    {
        return DB::transaction(function () use ($version): ContentHandoffRecord {
            $locked = ContentVersion::query()
                ->with('briefing.contentIdea.workAllocations')
                ->lockForUpdate()
                ->findOrFail($version->id);

            // Return the original result on retries. Never create a second handoff.
            $existing = ContentHandoffRecord::query()
                ->where('content_version_id', $locked->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            if ($locked->status !== ContentVersionStatus::APPROVED) {
                throw new LogicException('Only approved content versions can be handed off.');
            }

            $approval = ContentVersionApproval::query()
                ->where('content_version_id', $locked->id)
                ->where('decision', ContentApprovalDecision::APPROVED->value)
                ->where('content_hash', $locked->content_hash)
                ->latest('reviewed_at')
                ->first();

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

            $record = ContentHandoffRecord::create([
                'content_version_id' => $locked->id,
                'content_version_approval_id' => $approval->id,
                'idempotency_key' => sprintf('content-version:%d:simulated-handoff:v1', $locked->id),
                'payload' => $payload,
                'status' => 'handed_off',
                'handed_off_at' => Carbon::now(),
            ]);

            $locked->status = ContentVersionStatus::HANDED_OFF;
            $locked->save();

            return $record;
        });
    }
}
