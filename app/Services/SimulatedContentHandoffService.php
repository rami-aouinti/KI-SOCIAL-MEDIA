<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContentApprovalDecision;
use App\Enums\ContentVersionStatus;
use App\Models\ContentHandoffRecord;
use App\Models\ContentVersion;
use App\Models\ContentVersionApproval;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class SimulatedContentHandoffService
{
    public function handoff(ContentVersion $version): ContentHandoffRecord
    {
        return DB::transaction(function () use ($version): ContentHandoffRecord {
            $locked = ContentVersion::query()
                ->with('briefing.contentIdea')
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

            $snapshot = $locked->snapshot ?? [];
            $idea = $locked->briefing?->contentIdea;
            $errors = [];

            if (blank($locked->media_reference)) {
                $errors['media_reference'] = 'A media reference is required for handoff.';
            }

            if (empty($locked->channels)) {
                $errors['channels'] = 'At least one target channel is required.';
            }

            if (! $locked->scheduled_at) {
                $errors['scheduled_at'] = 'A scheduled date is required.';
            }

            if (! $idea || blank($idea->responsible_person)) {
                $errors['responsible_person'] = 'A responsible person is required.';
            }

            foreach ($locked->channels ?? [] as $channel) {
                if (blank($snapshot['channel_variants'][$channel]['text'] ?? null)) {
                    $errors['channel_variants'] = "A text variant is required for channel: {$channel}.";
                    break;
                }
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

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
