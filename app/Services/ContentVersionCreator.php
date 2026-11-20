<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContentBriefing;
use App\Models\ContentVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ContentVersionCreator
{
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
        ])->validate();

        return DB::transaction(function () use ($briefing, $validated): ContentVersion {
            $lockedBriefing = ContentBriefing::query()
                ->whereKey($briefing->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $nextVersion = ((int) $lockedBriefing->versions()
                ->max('version_number')) + 1;

            $snapshot = [
                'hook' => $validated['hook'],
                'body' => $validated['body'],
                'channel_variants' => $validated['channel_variants'],
            ];

            $canonicalSnapshot = json_encode(
                $snapshot,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            return $lockedBriefing->versions()->create([
                'version_number' => $nextVersion,
                'snapshot' => $snapshot,
                'media_reference' => $validated['media_reference'] ?? null,
                'channels' => array_values(array_unique($validated['channels'])),
                'scheduled_at' => $validated['scheduled_at'],
                'content_hash' => hash('sha256', $canonicalSnapshot),
                'status' => 'draft',
            ]);
        });
    }
}
