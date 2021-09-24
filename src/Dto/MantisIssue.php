<?php
/*
 * This file is part of the Artemeon Core - Web Application Framework.
 *
 * (c) Artemeon <www.artemeon.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Artemeon\M2G\Dto;

class MantisIssue
{
    private int $id;
    private string $summary;
    private string $description;
    private string $project;
    private string $status;
    private ?string $upstreamTicket;
    private ?int $upstreamTicketFieldId;
    private ?string $upstreamTicketFieldName;
    private ?string $issueUrl;

    public function __construct(
        int $id,
        string $summary,
        string $description,
        string $project,
        string $status,
        ?string $issueUrl,
        ?string $upstreamTicket,
        ?int $upstreamTicketFieldId,
        ?string $upstreamTicketFieldName
    )
    {
        $this->id = $id;
        $this->summary = $summary;
        $this->description = $description;
        $this->project = $project;
        $this->status = $status;
        $this->upstreamTicket = $upstreamTicket;
        $this->issueUrl = $issueUrl;
        $this->upstreamTicketFieldId = $upstreamTicketFieldId;
        $this->upstreamTicketFieldName = $upstreamTicketFieldName;
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