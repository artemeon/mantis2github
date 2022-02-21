<?php

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

    public function getMantisUrl(): string
    {
        return $this->mantisUrl;
    }

    public function getMantisToken(): string
    {
        return $this->mantisToken;
    }

    public function getGithubToken(): string
    {
        return $this->githubToken;
    }

    public function getGithubRepo(): string
    {
        return $this->githubRepo;
    }
}
