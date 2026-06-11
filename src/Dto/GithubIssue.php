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

    public static function fromMantisIssue(MantisIssue $issue, string $mantisBaseUrl): self
    {
        $issueBadge = '[![MANTIS-' . $issue->id . '](https://img.shields.io/badge/MANTIS-' . $issue->id . '-green?style=for-the-badge)](' . $issue->issueUrl . ')';

        $description = $issue->description;

        $attachments = self::buildAttachmentsSection($issue, $mantisBaseUrl);
        if ($attachments !== null) {
            $description .= PHP_EOL . PHP_EOL . $attachments;
        }

        $description .= PHP_EOL . PHP_EOL . $issueBadge;

        return new self(
            title: $issue->summary . ' [' . $issue->project . '] [MANTIS-' . $issue->id . ']',
            description: $description,
        );
    }

    /**
     * Build a "## Attachments" section linking each Mantis attachment to its download URL.
     *
     * GitHub cannot render the auth-gated Mantis images inline, so every attachment
     * (images included) is listed as a plain link to its Mantis file download.
     */
    private static function buildAttachmentsSection(MantisIssue $issue, string $mantisBaseUrl): ?string
    {
        if ($issue->attachments === []) {
            return null;
        }

        $baseUrl = rtrim($mantisBaseUrl, '/') . '/';

        $lines = ['## Attachments', ''];
        foreach ($issue->attachments as $attachment) {
            $downloadUrl = $baseUrl . 'file_download.php?file_id=' . $attachment->getId() . '&type=bug';
            $lines[] = '- [' . $attachment->getFilename() . '](' . $downloadUrl . ')';
        }

        return implode(PHP_EOL, $lines);
    }
}
