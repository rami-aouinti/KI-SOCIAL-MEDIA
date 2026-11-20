<?php

declare(strict_types=1);

namespace App\\Services;

use App\\Models\\ContentVersion;
use Illuminate\\Validation\\ValidationException;

class ContentVersionReadinessValidator
{
    /**
     * Validate the immutable content snapshot and its required operational context
     * before either human approval or simulated handoff.
     */
    public function assertReady(ContentVersion $version): void
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
            $errors['media_reference'] = 'A media reference is required.';
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
