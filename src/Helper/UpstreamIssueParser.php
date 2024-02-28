<?php

declare(strict_types=1);

namespace Artemeon\M2G\Helper;

class UpstreamIssueParser
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function parse(string $input): array
    {
        if (!$input) {
            return [];
        }

        $parts = explode(' ', $input);

        $issues = [];

        foreach ($parts as $part) {
            $trimmedPart = trim($part);

            if (!str_starts_with($trimmedPart, 'https://github.com/')) {
                continue;
            }

            if (!preg_match('/\/issues\/(\d+)$/', $trimmedPart, $matches)) {
                continue;
            }

            $issues[] = ['url' => $part, 'id' => $matches[1]];
        }

        return $issues;
    }
}
