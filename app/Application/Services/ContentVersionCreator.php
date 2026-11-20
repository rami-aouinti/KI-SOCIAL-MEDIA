<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTO\ContentVersionData;
use App\Infrastructure\Persistence\Eloquent\Models\ContentBriefing;
use App\Infrastructure\Persistence\Eloquent\Models\ContentVersion;
use App\Domain\Repositories\ContentVersionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ContentVersionCreator
{
    public function __construct(
        private readonly ContentVersionRepositoryInterface $versions,
    ) {
    }

    /**
     * Create an immutable content snapshot for review and later approval.
     *
     * @param array<string, mixed> $data
     */
    public function create(ContentBriefing $briefing, array $data): ContentVersion
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

        return DB::transaction(function () use ($briefing, $versionData): ContentVersion {
            $lockedBriefing = $this->versions->lockBriefingForVersionCreation(
                (int) $briefing->getKey()
            );

            $snapshot = $versionData->snapshot();

            // The approval fingerprint covers copy, variants, media, channels,
            // schedule and generation provenance.
            $approvalPayload = $versionData->approvalPayload();
            $canonicalSnapshot = json_encode(
                $approvalPayload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            return $this->versions->createNextForBriefing($lockedBriefing, [
                'snapshot' => $snapshot,
                'media_reference' => $versionData->mediaReference,
                'channels' => $versionData->channels,
                'scheduled_at' => $versionData->scheduledAt,
                'content_hash' => hash('sha256', $canonicalSnapshot),
                'status' => 'draft',
            ]);
        });
    }
}
