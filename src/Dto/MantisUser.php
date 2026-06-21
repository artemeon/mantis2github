<?php

declare(strict_types=1);

namespace Artemeon\M2G\Dto;

final class MantisUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $realName,
        public readonly ?string $email,
        public readonly ?string $accessLevel,
    ) {
    }
}
