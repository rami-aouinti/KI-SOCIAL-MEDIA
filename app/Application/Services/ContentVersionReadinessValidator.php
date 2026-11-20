<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Content\Records\ContentVersionRecord;
use Illuminate\Validation\ValidationException;

final class ContentVersionReadinessValidator
{
    public function assertReady(ContentVersionRecord $version): void
    {
        $errors = [];
        $snapshot = $version->snapshot;

        if (trim((string) ($snapshot['hook'] ?? '')) === '') {
            $errors['hook'] = 'A hook is required.';
        }

        if (trim((string) ($snapshot['body'] ?? '')) === '') {
            $errors['body'] = 'A text body is required.';
        }

        if (blank($version->mediaReference)) {
            $errors['media_reference'] = 'A media reference is required.';
        }

        if ($version->channels === []) {
            $errors['channels'] = 'At least one channel is required.';
        }

        if ($version->scheduledAt === null) {
            $errors['scheduled_at'] = 'A publication date is required.';
        }

        if (blank($version->responsiblePerson)) {
            $errors['responsible_person'] = 'A responsible person must be assigned.';
        }

        if ($version->workAllocationCount < 1) {
            $errors['work_allocations'] = 'At least one work allocation is required.';
        }

        $variants = $snapshot['channel_variants'] ?? [];
        foreach ($version->channels as $channel) {
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
