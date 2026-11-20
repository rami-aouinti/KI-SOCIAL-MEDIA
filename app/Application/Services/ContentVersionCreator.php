<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTO\ContentVersionData;
use App\Domain\Content\Records\ContentVersionRecord;
use App\Domain\Content\ValueObjects\ContentVersionSnapshot;
use App\Domain\Repositories\ContentVersionRepositoryInterface;
use App\Domain\Transactions\TransactionManagerInterface;
use Illuminate\Support\Facades\Validator;

final class ContentVersionCreator
{
    public function __construct(
        private readonly ContentVersionRepositoryInterface $versions,
        private readonly TransactionManagerInterface $transactions,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $briefingId, array $data): ContentVersionRecord
    {
        $validated = Validator::make($data, [
            'hook' => ['required', 'string'],
            'body' => ['required', 'string'],
            'channel_variants' => ['required', 'array', 'min:1'],
            'channel_variants.*' => ['required', 'array'],
            'channel_variants.*.text' => ['required', 'string'],
            'media_reference' => ['nullable', 'string', 'max:500'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['required', 'string', 'in:instagram,facebook,tiktok,linkedin'],
            'scheduled_at' => ['required', 'date_format:Y-m-d'],
            'generation' => ['nullable', 'array'],
            'generation.provider' => ['required_with:generation', 'string', 'max:50'],
            'generation.simulated' => ['required_with:generation', 'boolean'],
            'generation.model' => ['nullable', 'string', 'max:100'],
        ])->validate();

        $versionData = ContentVersionData::fromValidatedArray($validated);

        return $this->transactions->run(function () use ($briefingId, $versionData): ContentVersionRecord {
            $briefing = $this->versions->lockBriefingForVersionCreation($briefingId);
            $snapshot = ContentVersionSnapshot::fromApprovalPayload($versionData->approvalPayload());

            return $this->versions->createNextForBriefing($briefing->id, [
                'snapshot' => $snapshot->content(),
                'media_reference' => $versionData->mediaReference,
                'channels' => $versionData->channels,
                'scheduled_at' => $versionData->scheduledAt,
                'content_hash' => $snapshot->hash(),
                'status' => 'draft',
            ]);
        });
    }
}
