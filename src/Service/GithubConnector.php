<?php
/*
 * This file is part of the Artemeon Core - Web Application Framework.
 *
 * (c) Artemeon <www.artemeon.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Artemeon\M2G\Service;

use Artemeon\M2G\Config\ConfigValues;
use Artemeon\M2G\Dto\GithubIssue;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GithubConnector
{
    private ?ConfigValues $config;

    public function __construct(?ConfigValues $config)
    {
        $this->config = $config;
    }

    public function readIssue(int $number): ?GithubIssue
    {
        try {
            $response = $this->getDefaultClient()->get(
                '/repos/' . $this->config->getGithubRepo() . '/issues/' . $number
            );
            $result = json_decode($response->getBody(), true);
        } catch (GuzzleException | Exception $e) {
            return null;
        }

        return new GithubIssue(
            $result['id'],
            $result['number'],
            $result['title'],
            $result['body'] ?? '',
            $result['html_url'],
            $result['state'],
            $result['assignees'],
            $result['labels'],
        );
    }

    /**
     * @throws GuzzleException
     */
    public function createIssue(GithubIssue $issue): GithubIssue
    {
        $response = $this->getDefaultClient()->post(
            '/repos/' . $this->config->getGithubRepo() . '/issues',
            [
                'body' => json_encode([
                    'title' => $issue->getTitle(),
                    'body' => $issue->getDescription(),
                    'labels' => $issue->getLabels(),
                ]),
            ],
        );

        $result = json_decode($response->getBody(), true);

        return new GithubIssue(
            $result['id'],
            $result['number'],
            $result['title'],
            $result['body'],
            $result['html_url'],
            $result['state'],
            $result['assignees'],
            $result['labels'],
        );
    }

    public function getLabels(): array
    {
        try {
            $response = $this->getDefaultClient()->get(
                '/repos/' . $this->config->getGithubRepo() . '/labels',
            );
            $result = json_decode($response->getBody(), true);
        } catch (GuzzleException | Exception $e) {
            return [];
        }

        return $result;
    }

    private function getDefaultClient(): Client
    {
        return new Client([
            'base_uri' => 'https://api.github.com/',
            'headers' => [
                'Authorization' => 'token ' . $this->config->getGithubToken(),
                'accept' => 'application/vnd.github.v3+json',
            ],
        ]);
    }
}
