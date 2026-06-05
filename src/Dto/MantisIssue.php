<?php

declare(strict_types=1);

namespace Artemeon\M2G\Dto;

class MantisIssue
{
    /**
     * @param MantisAttachment[] $attachments
     */
    public function __construct(
        private readonly int $id,
        private readonly string $summary,
        private readonly string $description,
        private readonly string $project,
        private readonly string $status,
        private readonly string $resolution,
        private readonly ?string $assignee,
        private readonly ?string $issueUrl,
        private ?string $upstreamTicket = null,
        private ?int $upstreamTicketFieldId = null,
        private ?string $upstreamTicketFieldName = null,
        private readonly array $attachments = [],
    ) {
    }

    /**
     * @return MantisAttachment[]
     */
    final public function getAttachments(): array
    {
        return $this->attachments;
    }

    final public function getId(): int
    {
        return $this->id;
    }

    final public function getSummary(): string
    {
        return $this->summary;
    }

    final public function getDescription(): string
    {
        return $this->description;
    }

    final public function getProject(): string
    {
        return $this->project;
    }

    final public function getStatus(): string
    {
        return $this->status;
    }

    final public function getResolution(): string
    {
        return $this->resolution;
    }

    final public function getAssignee(): ?string
    {
        return $this->assignee;
    }

    final public function getUpstreamTicket(): ?string
    {
        return $this->upstreamTicket;
    }

    final public function getIssueUrl(): ?string
    {
        return $this->issueUrl;
    }

    final public function getUpstreamTicketFieldId(): ?int
    {
        return $this->upstreamTicketFieldId;
    }

    final public function getUpstreamTicketFieldName(): ?string
    {
        return $this->upstreamTicketFieldName;
    }

    final public function setUpstreamTicket(?string $upstreamTicket): void
    {
        $this->upstreamTicket = $upstreamTicket;
    }

    final public function setUpstreamTicketFieldId(?int $upstreamTicketFieldId): void
    {
        $this->upstreamTicketFieldId = $upstreamTicketFieldId;
    }

    final public function setUpstreamTicketFieldName(?string $upstreamTicketFieldName): void
    {
        $this->upstreamTicketFieldName = $upstreamTicketFieldName;
    }
}
