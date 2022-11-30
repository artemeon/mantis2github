<?php

namespace Artemeon\M2G\Service;

use Artemeon\M2G\Config\ConfigValues;
use Artemeon\M2G\Dto\GithubIssue;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GithubConnector
{
    private Client $client;

    public function __construct(private ?ConfigValues $config)
    {
        if (!$config) {
            return;
        }
        $this->client = new Client([
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'Authorization' => 'token ' . $this->config->getGithubToken(),
            ],
            'verify' => false,
            'base_uri' => 'https://api.github.com/repos/' . $config->getGithubRepo() . '/',
        ]);
    }

    public function readIssue(int $number): ?GithubIssue
    {
        try {
            $response = $this->client->get(
                'issues/' . $number,
            );
            $result = json_decode($response->getBody(), true);
        } catch (GuzzleException | Exception) {
            return null;
        }

        return new GithubIssue(
            id: $result['id'],
            number: $result['number'],
            title: $result['title'],
            description: $result['body'] ?? '',
            issueUrl: $result['html_url'],
            state: $result['state'],
            assignees: $result['assignees'],
            labels: $result['labels'],
        );
    }

    public function createIssue(GithubIssue $issue): ?GithubIssue
    {
        try {
            $response = $this->client->post(
                'issues',
                [
                    'body' => json_encode([
                        'title' => $issue->getTitle(),
                        'body' => $issue->getDescription(),
                        'labels' => $issue->getLabels(),
                    ]),
                ],
            );
        } catch (GuzzleException | Exception) {
            return null;
        }

        $result = json_decode($response->getBody(), true);

        return new GithubIssue(
            id: $result['id'],
            number: $result['number'],
            title: $result['title'],
            description: $result['body'],
            issueUrl: $result['html_url'],
            state: $result['state'],
            assignees: $result['assignees'],
            labels: $result['labels'],
        );
    }

    public function getLabels(): array
    {
        try {
            $response = $this->client->get('labels');
            $result = json_decode($response->getBody(), true);
        } catch (GuzzleException | Exception) {
            return [];
        }

        return $result ?: [];
    }
}
