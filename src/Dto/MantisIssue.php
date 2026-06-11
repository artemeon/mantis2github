<?php

declare(strict_types=1);

namespace Artemeon\M2G\Dto;

final class MantisIssue
{
    /**
     * @param MantisAttachment[] $attachments
     * @param MantisNote[] $notes
     */
    public function __construct(
        public readonly int $id,
        public readonly string $summary,
        public readonly string $description,
        public readonly string $project,
        public readonly string $status,
        public readonly string $resolution,
        public readonly ?string $assignee,
        public readonly ?string $issueUrl,
        public ?string $upstreamTicket = null,
        public ?int $upstreamTicketFieldId = null,
        public ?string $upstreamTicketFieldName = null,
        public readonly array $attachments = [],
        public readonly array $notes = [],
    ) {
    }
}
