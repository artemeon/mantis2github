<?php

declare(strict_types=1);

namespace Artemeon\M2G\Config;

class ConfigValues
{
    public function __construct(private string $mantisUrl, private string $mantisToken, private string $githubToken, private string $githubRepo)
    {
    }

    final public function getMantisUrl(): string
    {
        return $this->mantisUrl;
    }

    final public function getMantisToken(): string
    {
        return $this->mantisToken;
    }

    final public function getGithubToken(): string
    {
        return $this->githubToken;
    }

    final public function getGithubRepo(): string
    {
        return $this->githubRepo;
    }
}
