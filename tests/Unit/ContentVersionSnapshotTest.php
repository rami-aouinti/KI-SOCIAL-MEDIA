<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Content\ValueObjects\ContentVersionSnapshot;
use PHPUnit\Framework\TestCase;

final class ContentVersionSnapshotTest extends TestCase
{
    public function test_it_keeps_the_content_snapshot_and_generates_a_sha256_fingerprint(): void
    {
        $payload = [
            'snapshot' => [
                'hook' => 'A clear hook',
                'body' => 'A complete body',
                'channel_variants' => [
                    'instagram' => ['text' => 'Instagram copy'],
                ],
            ],
            'media_reference' => 'synthetic://media/1',
            'channels' => ['instagram'],
            'scheduled_at' => '2026-11-23',
        ];

        $snapshot = ContentVersionSnapshot::fromApprovalPayload($payload);

        self::assertSame($payload['snapshot'], $snapshot->content());
        self::assertSame($payload, $snapshot->approvalPayload());
        self::assertSame(
            hash('sha256', json_encode(
                $payload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            )),
            $snapshot->hash(),
        );
    }

    public function test_it_rejects_a_payload_without_a_content_snapshot(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ContentVersionSnapshot::fromApprovalPayload([
            'media_reference' => null,
            'channels' => ['instagram'],
        ]);
    }
}
