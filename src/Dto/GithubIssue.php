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

    public function __construct(?int $id, ?int $number, string $summary, string $description, string $issueUrl)
    {
        $this->id = $id;
        $this->title = $summary;
        $this->description = $description;
        $this->number = $number;
        $this->issueUrl = $issueUrl;
    }


    public static function fromMantisIssue(MantisIssue $issue): GithubIssue
    {
        return new GithubIssue(
            null,
            null,
            $issue->getSummary(),
            $issue->getIssueUrl() . PHP_EOL . PHP_EOL . $issue->getDescription(),
            ''
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


}