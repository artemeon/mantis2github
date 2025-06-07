<?php

declare(strict_types=1);

namespace Artemeon\M2G\Config;

final readonly class ConfigValues
{
    public function __construct(
        public string $mantisUrl,
        public string $mantisToken,
        public string $githubToken,
        public string $githubRepo,
    ) {
    }
}
