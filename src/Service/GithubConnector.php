<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service;

use Artemeon\M2G\Config\ConfigValues;
use Artemeon\M2G\Dto\GithubIssue;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JsonException;

class GithubConnector
{
    private Client $client;

    public function __construct(?ConfigValues $config)
    {
        $this->client = new Client([
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'Authorization' => 'token ' . $config?->getGithubToken(),
            ],
            'verify' => false,
            'base_uri' => 'https://api.github.com/repos/' . $config?->getGithubRepo() . '/',
        ]);
    }

    final public function readIssue(int $number): ?GithubIssue
    {
        try {
            $response = $this->client->get(
                'issues/' . $number,
            );
            /**
             * @var array{
             *     id: int,
             *     number: int,
             *     title: string,
             *     body: ?string,
             *     html_url: string,
             *     state: string,
             *     assignees: array{
             *         html_url: string,
             *         login: string,
             *     }[],
             *     labels: array{
             *         id: int,
             *         name: string,
             *         color: string,
             *     }[],
             * } $result
             */
            $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception | GuzzleException) {
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

    /**
     * @throws JsonException
     */
    final public function createIssue(GithubIssue $issue): ?GithubIssue
    {
        try {
            $response = $this->client->post(
                'issues',
                [
                    'body' => json_encode([
                        'title' => $issue->getTitle(),
                        'body' => $issue->getDescription(),
                        'labels' => $issue->getLabels(),
                    ], JSON_THROW_ON_ERROR),
                ],
            );
        } catch (Exception | GuzzleException) {
            return null;
        }

        /**
         * @var array{
         *     id: int,
         *     number: int,
         *     title: string,
         *     body: ?string,
         *     html_url: string,
         *     state: string,
         *     assignees: array{
         *         html_url: string,
         *         login: string,
         *     }[],
         *     labels: array{
         *         id: int,
         *         name: string,
         *         color: string,
         *     }[],
         * } $result
         */
        $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

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

    /**
     * @return array{
     *     name: string,
     * }[]
     */
    final public function getLabels(): array
    {
        try {
            $response = $this->client->get('labels');
            /**
             * @var array{
             *     name: string,
             * }[] $result
             */
            $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception | GuzzleException) {
            return [];
        }

        return $result ?: [];
    }

    /**
     * @return array{ data: mixed }
     */
    final public function graphql(string $query): array
    {
        try {
            $response = $this->client->post('https://api.github.com/graphql', [
                RequestOptions::JSON => [
                    'query' => $query,
                ],
            ]);

            /**
             * @var ?array{ data: mixed } $result
             */
            $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception | GuzzleException) {
            return ['data' => null];
        }

        return $result ?? ['data' => null];
    }
}
