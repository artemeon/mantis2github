<?php

declare(strict_types=1);

namespace Artemeon\M2G\Config;

class ConfigValues
{
    private string $mantisUrl;
    private string $mantisToken;

    private string $githubToken;
    private string $githubRepo;

    /**
     * @param string $mantisUrl
     * @param string $mantisToken
     * @param string $githubToken
     * @param string $githubRepo
     */
    public function __construct(string $mantisUrl, string $mantisToken, string $githubToken, string $githubRepo)
    {
        $this->mantisUrl = $mantisUrl;
        $this->mantisToken = $mantisToken;
        $this->githubToken = $githubToken;
        $this->githubRepo = $githubRepo;
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
