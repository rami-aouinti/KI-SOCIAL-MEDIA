<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Content\Entities\ContentVersionWorkflow;
use App\Domain\Content\Records\ContentHandoffRecord;
use App\Domain\Events\ContentHandedOffEvent;
use App\Domain\Events\WorkflowEventPublisherInterface;
use App\Domain\Repositories\ContentApprovalRepositoryInterface;
use App\Domain\Repositories\ContentHandoffRepositoryInterface;
use App\Domain\Repositories\ContentVersionRepositoryInterface;
use App\Domain\Shared\Enums\ContentApprovalDecision;
use App\Domain\Transactions\TransactionManagerInterface;
use Illuminate\Validation\ValidationException;

final class SimulatedContentHandoffService
{
    public function __construct(
        private readonly ContentVersionReadinessValidator $readinessValidator,
        private readonly ContentVersionRepositoryInterface $versions,
        private readonly ContentApprovalRepositoryInterface $approvals,
        private readonly ContentHandoffRepositoryInterface $handoffs,
        private readonly TransactionManagerInterface $transactions,
        private readonly WorkflowEventPublisherInterface $events,
    ) {}

    public function handoff(int $versionId): ContentHandoffRecord
    {
        return $this->transactions->run(function () use ($versionId): ContentHandoffRecord {
            $locked = $this->versions->lockForWorkflow($versionId, true);
            $existing = $this->handoffs->findForVersion($locked->id);

            if ($existing !== null) {
                return $existing;
            }

            $next = ContentVersionWorkflow::fromStatus($locked->status)->handOff();
            $approval = $this->approvals->findLatestForVersionAndHash(
                $locked->id,
                ContentApprovalDecision::APPROVED,
                $locked->contentHash,
            );

            if ($approval === null) {
                throw ValidationException::withMessages([
                    'approval' => 'A valid approval for this exact content version is required.',
                ]);
            }

            $this->readinessValidator->assertReady($locked);
            $snapshot = $locked->snapshot;

            $record = $this->handoffs->create([
                'content_version_id' => $locked->id,
                'content_version_approval_id' => $approval->id,
                'idempotency_key' => sprintf('content-version:%d:simulated-handoff:v1', $locked->id),
                'payload' => [
                    'mode' => 'simulated',
                    'destination' => 'simulated-publishing-interface',
                    'content_version_id' => $locked->id,
                    'version_number' => $locked->versionNumber,
                    'content_hash' => $locked->contentHash,
                    'hook' => $snapshot['hook'],
                    'body' => $snapshot['body'],
                    'channel_variants' => $snapshot['channel_variants'],
                    'media_reference' => $locked->mediaReference,
                    'channels' => $locked->channels,
                    'scheduled_at' => $locked->scheduledAt,
                    'responsible_person' => $locked->responsiblePerson,
                ],
                'status' => 'handed_off',
                'handed_off_at' => now(),
            ]);

            $this->versions->updateStatus($locked->id, $next->status());
            $this->events->publish(new ContentHandedOffEvent($locked->id));

            return $record;
        });
    }
}
