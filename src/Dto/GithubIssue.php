<?php

declare(strict_types=1);

namespace Artemeon\M2G\Dto;

class GithubIssue
{
    /**
     * @param array{
     *     html_url: string,
     *     login: string,
     * }[] $assignees
     * @param array{
     *     id: int,
     *     name: string,
     *     color: string,
     * }[] $labels
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?int $number = null,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?string $issueUrl = null,
        public readonly string $state = 'open',
        public readonly array $assignees = [],
        public array $labels = [],
    ) {
    }

    public static function fromMantisIssue(MantisIssue $issue): self
    {
        $issueBadge = '[![MANTIS-' . $issue->id . '](https://img.shields.io/badge/MANTIS-' . $issue->id . '-green?style=for-the-badge)](' . $issue->issueUrl . ')';

        return new self(
            title: $issue->summary . ' [' . $issue->project . '] [MANTIS-' . $issue->id . ']',
            description: $issue->description . PHP_EOL . PHP_EOL . $issueBadge,
        );
    }
}
