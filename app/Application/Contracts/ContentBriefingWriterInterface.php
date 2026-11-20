<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Application\DTO\BriefingIdeaData;
use App\Application\DTO\GeneratedBriefingData;
use App\Domain\Content\Records\ContentBriefingRecord;

interface ContentBriefingWriterInterface
{
    public function findIdea(int $ideaId): BriefingIdeaData;

    public function create(int $ideaId, GeneratedBriefingData $data): ContentBriefingRecord;
}
