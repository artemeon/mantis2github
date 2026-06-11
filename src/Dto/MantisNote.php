<?php

declare(strict_types=1);

namespace Artemeon\M2G\Dto;

class MantisNote
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $reporter,
        public readonly string $text,
        public readonly ?string $createdAt = null,
        public readonly ?string $viewState = null,
    ) {
    }
}
