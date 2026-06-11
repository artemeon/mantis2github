<?php

declare(strict_types=1);

namespace Artemeon\M2G\Dto;

readonly class SyncResult
{
    public function __construct(
        public int $mantisId,
        public SyncStatus $status,
        public ?string $githubUrl = null,
        public ?string $detail = null,
    ) {
    }
}
