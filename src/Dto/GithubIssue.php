<?php

namespace Artemeon\M2G\Dto;

use JetBrains\PhpStorm\Pure;

class GithubIssue
{
    public function __construct(
        private ?int $id = null,
        private ?int $number = null,
        private ?string $title = null,
        private ?string $description = null,
        private ?string $issueUrl = null,
        private string $state = 'open',
        private array $assignees = [],
        private array $labels = [],
    ) {
    }

    #[Pure]
    public static function fromMantisIssue(MantisIssue $issue): GithubIssue
    {
        return new self(
            title: $issue->getSummary(),
            description: $issue->getIssueUrl() . PHP_EOL . PHP_EOL . $issue->getDescription(),
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIssueUrl(): ?string
    {
        return $this->issueUrl;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getAssignees(): array
    {
        return $this->assignees;
    }

    public function setLabels(array $labels = []): self
    {
        $this->labels = $labels;

        return $this;
    }

    public function getLabels(): array
    {
        return $this->labels ?? [];
    }
}
