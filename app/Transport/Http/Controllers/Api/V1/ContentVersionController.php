<?php

declare(strict_types=1);

namespace App\Transport\Http\Controllers\Api\V1;

use App\Application\Services\ContentApprovalService;
use App\Application\Services\ContentVersionCreator;
use App\Domain\Content\Records\ContentVersionRecord;
use App\Infrastructure\Persistence\Eloquent\Models\ContentVersion;
use App\Transport\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ContentVersionController extends Controller
{
    public function store(
        int $briefing,
        Request $request,
        ContentVersionCreator $creator,
    ): JsonResponse {
        $version = $creator->create($briefing, $request->all());

        return response()->json(['data' => $this->serializeRecord($version)], 201);
    }

    public function submitForReview(int $contentVersion, ContentApprovalService $approvals): JsonResponse
    {
        $version = $approvals->submitForReview($contentVersion);

        return response()->json(['data' => $this->serializeRecord($version)]);
    }

    public function approve(
        int $contentVersion,
        Request $request,
        ContentApprovalService $approvals,
    ): JsonResponse {
        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);
        $version = $approvals->approve(
            $contentVersion,
            (string) $request->user()->name,
            $validated['comment'] ?? null,
        );

        return response()->json(['data' => $this->serializeRecord($version)]);
    }

    public function reject(
        int $contentVersion,
        Request $request,
        ContentApprovalService $approvals,
    ): JsonResponse {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:3', 'max:5000'],
        ]);
        $version = $approvals->reject(
            $contentVersion,
            (string) $request->user()->name,
            $validated['comment'],
        );

        return response()->json(['data' => $this->serializeRecord($version)]);
    }

    public function show(int $contentVersion): JsonResponse
    {
        $version = ContentVersion::query()
            ->with(['approvals', 'handoffRecords'])
            ->findOrFail($contentVersion);

        return response()->json([
            'data' => [
                'id' => $version->getKey(),
                'briefing_id' => $version->content_briefing_id,
                'version_number' => $version->version_number,
                'snapshot' => $version->snapshot,
                'media_reference' => $version->media_reference,
                'channels' => $version->channels,
                'scheduled_at' => $version->scheduled_at?->toDateString(),
                'content_hash' => $version->content_hash,
                'status' => $version->status->value,
                'approvals' => $version->approvals->map(fn ($approval): array => [
                    'reviewer' => $approval->reviewer,
                    'decision' => $approval->decision->value,
                    'comment' => $approval->comment,
                    'reviewed_at' => $approval->reviewed_at?->toISOString(),
                ])->all(),
                'handoffs' => $version->handoffRecords->map(fn ($handoff): array => [
                    'status' => $handoff->status,
                    'handed_off_at' => $handoff->handed_off_at?->toISOString(),
                ])->all(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRecord(ContentVersionRecord $version): array
    {
        return [
            'id' => $version->id,
            'briefing_id' => $version->briefingId,
            'version_number' => $version->versionNumber,
            'snapshot' => $version->snapshot,
            'media_reference' => $version->mediaReference,
            'channels' => $version->channels,
            'scheduled_at' => $version->scheduledAt,
            'content_hash' => $version->contentHash,
            'status' => $version->status->value,
        ];
    }
}
