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

    public static function fromMantisIssue(MantisIssue $issue): GithubIssue
    {
        $issueBadge = '[![MANTIS-' . $issue->getId() . '](https://img.shields.io/badge/MANTIS-' . $issue->getId() . '-green?style=for-the-badge)](' . $issue->getIssueUrl() . ')';

        return new self(
            title: '[MANTIS-' . $issue->getId() . '] [' . $issue->getProject() . '] ' . $issue->getSummary(),
            description: $issue->getDescription() . PHP_EOL . PHP_EOL . $issueBadge,
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

    /**
     * @return array{
     *     html_url: string,
     *     login: string,
     * }[]
     */
    final public function getAssignees(): array
    {
        return $this->assignees;
    }

    /**
     * @param array{
     *     id: int,
     *     name: string,
     *     color: string,
     * }[] $labels
     */
    final public function setLabels(array $labels = []): self
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     color: string,
     * }[]
     */
    final public function getLabels(): array
    {
        return $this->labels;
    }
}
