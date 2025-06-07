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
        private readonly ?int $id = null,
        private readonly ?int $number = null,
        private readonly ?string $title = null,
        private readonly ?string $description = null,
        private readonly ?string $issueUrl = null,
        private readonly string $state = 'open',
        private readonly array $assignees = [],
        private array $labels = [],
    ) {
    }

    public static function fromMantisIssue(MantisTicket $mantisIssue): GithubIssue
    {
        $issueBadge = '[![MANTIS-' . $mantisIssue->getId() . '](https://img.shields.io/badge/MANTIS-' . $mantisIssue->getId() . '-green?style=for-the-badge)](' . $mantisIssue->getIssueUrl() . ')';

        return new self(
            title: $mantisIssue->getSummary() . ' [' . $mantisIssue->getProject() . '] [MANTIS-' . $mantisIssue->getId() . ']',
            description: $mantisIssue->getDescription() . PHP_EOL . PHP_EOL . $issueBadge,
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
