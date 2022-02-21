<?php

namespace Artemeon\M2G\Dto;

class MantisIssue
{
    public function __construct(
        private int $id,
        private string $summary,
        private string $description,
        private string $project,
        private string $status,
        private string $resolution,
        private ?string $assignee,
        private ?string $issueUrl,
        private ?string $upstreamTicket = null,
        private ?int $upstreamTicketFieldId = null,
        private ?string $upstreamTicketFieldName = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getProject(): string
    {
        return $this->project;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getResolution(): string
    {
        return $this->resolution;
    }

    public function getAssignee(): ?string
    {
        return $this->assignee;
    }

    public function getUpstreamTicket(): ?string
    {
        return $this->upstreamTicket;
    }

    public function getIssueUrl(): ?string
    {
        return $this->issueUrl;
    }

    public function getUpstreamTicketFieldId(): ?int
    {
        return $this->upstreamTicketFieldId;
    }

    public function getUpstreamTicketFieldName(): ?string
    {
        return $this->upstreamTicketFieldName;
    }

    public function setUpstreamTicket(?string $upstreamTicket): void
    {
        $this->upstreamTicket = $upstreamTicket;
    }

    public function setUpstreamTicketFieldId(?int $upstreamTicketFieldId): void
    {
        $this->upstreamTicketFieldId = $upstreamTicketFieldId;
    }

    public function setUpstreamTicketFieldName(?string $upstreamTicketFieldName): void
    {
        $this->upstreamTicketFieldName = $upstreamTicketFieldName;
    }
}
