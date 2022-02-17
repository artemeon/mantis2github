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

class GithubIssue
{
    private ?int $id;
    private ?int $number;
    private string $title;
    private string $description;
    private string $issueUrl;
    private string $state;
    private array $assignees;
    private array $labels;

    public function __construct(
        ?int $id,
        ?int $number,
        string $summary,
        string $description,
        string $issueUrl,
        string $state = 'open',
        array $assignees = [],
        array $labels = []
    ) {
        $this->id = $id;
        $this->title = $summary;
        $this->description = $description;
        $this->number = $number;
        $this->issueUrl = $issueUrl;
        $this->state = $state;
        $this->assignees = $assignees;
        $this->labels = $labels;
    }

    public static function fromMantisIssue(MantisIssue $issue): GithubIssue
    {
        return new self(
            null,
            null,
            $issue->getSummary(),
            $issue->getIssueUrl() . PHP_EOL . PHP_EOL . $issue->getDescription(),
            '',
            '',
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function getIssueUrl(): string
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
        return $this->labels;
    }
}
