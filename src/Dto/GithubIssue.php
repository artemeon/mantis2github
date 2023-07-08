<?php

declare(strict_types=1);

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
        $rows = [
            '| Mantis Ticket |',
            '|:-------------:|',
            '| [MANTIS-' . $issue->getId() . '](' . $issue->getIssueUrl() . ') |',
        ];
        $table = implode(PHP_EOL, $rows);
        return new self(
            title: '[MANTIS-' . $issue->getId() . '] [' . $issue->getProject() . '] ' . $issue->getSummary(),
            description: $issue->getDescription() . PHP_EOL . PHP_EOL . $table,
        );
    }

    final public function getId(): ?int
    {
        return $this->id;
    }

    final public function getNumber(): ?int
    {
        return $this->number;
    }

    final public function getTitle(): ?string
    {
        return $this->title;
    }

    final public function getDescription(): ?string
    {
        return $this->description;
    }

    final public function getIssueUrl(): ?string
    {
        return $this->issueUrl;
    }

    final public function getState(): string
    {
        return $this->state;
    }

    final public function getAssignees(): array
    {
        return $this->assignees;
    }

    final public function setLabels(array $labels = []): self
    {
        $this->labels = $labels;

        return $this;
    }

    final public function getLabels(): array
    {
        return $this->labels ?? [];
    }
}
